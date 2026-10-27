<?php
declare(strict_types=1);

/**
 * Copyright 2016 - 2026, Cake Development Corporation (https://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2016 - 2026, Cake Development Corporation (https://cakedc.com)
 * @license MIT License (https://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Api\Test\TestCase\Service\Action\Extension;

use Authorization\AuthorizationService;
use Authorization\IdentityDecorator;
use Authorization\Policy\MapResolver;
use Cake\Http\ServerRequest;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;
use CakeDC\Api\Rbac\ApiRbac;
use CakeDC\Api\Service\Action\CrudAction;
use CakeDC\Api\Service\Action\Extension\CrudRelationsExtension;
use CakeDC\Api\Service\CrudService;
use CakeDC\Api\TestSuite\TestCase;
use CakeDC\Auth\Policy\RbacPolicy;
use CakeDC\Auth\Rbac\RbacInterface;
use ReflectionClass;
use ReflectionMethod;

/**
 * CrudRelations asks the authorization service whether the user may access a related service.
 */
class CrudRelationsAccessTest extends TestCase
{
    public function tearDown(): void
    {
        TableRegistry::getTableLocator()->clear();
        parent::tearDown();
    }

    /**
     * @param array|null $rules Permission rules, or null for a request without authorization.
     * @param string[] $paths Association paths to check.
     * @param array $options `nested` (bool), `policy` (object), `rbac` (request adapter) or `config` (extension settings).
     * @return bool[]
     */
    protected function access(?array $rules, array $paths, array $options = []): array
    {
        $request = new ServerRequest(['environment' => ['REQUEST_METHOD' => 'GET']]);
        $service = $this->createMock(CrudService::class);
        $action = $this->createMock(CrudAction::class);
        $action->method('getName')->willReturn('view');
        $action->method('getService')->willReturn($service);
        $action->method('getTable')->willReturn(TableRegistry::getTableLocator()->get('Articles'));
        $service->method('getAction')->willReturn($action);
        $service->method('getName')->willReturn('articles');
        $request = $request->withAttribute('service', $service);
        $authorized = $request;
        if ($rules !== null || isset($options['policy'])) {
            $resolver = new MapResolver();
            if (isset($options['policy'])) {
                $resolver->map(ServerRequest::class, $options['policy']);
            } else {
                $rbac = new ApiRbac();
                $rbac->setPermissions($rules);
                $resolver->map(ServerRequest::class, new RbacPolicy(['adapter' => $rbac]));
            }
            $authorization = new AuthorizationService($resolver);
            $authorized = $request
                ->withAttribute('authorization', $authorization)
                ->withAttribute('identity', new IdentityDecorator($authorization, ['id' => 1, 'role' => 'user']));
        }
        if (isset($options['rbac'])) {
            $authorized = $authorized->withAttribute('rbac', $options['rbac']);
        }
        if (!empty($options['nested'])) {
            // a nested service keeps the request from before the authorization middleware
            $root = $this->createMock(CrudService::class);
            $root->method('getParentService')->willReturn(null);
            $root->method('getRequest')->willReturn($authorized);
            $service->method('getParentService')->willReturn($root);
            $service->method('getRequest')->willReturn($request);
        } else {
            $service->method('getParentService')->willReturn(null);
            $service->method('getRequest')->willReturn($authorized);
        }

        $extension = (new ReflectionClass(CrudRelationsExtension::class))->newInstanceWithoutConstructor();
        $extension->setConfig($options['config'] ?? []);
        $method = new ReflectionMethod($extension, '_isRelationAccessible');
        $method->setAccessible(true);
        $result = [];
        foreach ($paths as $path) {
            $result[$path] = $method->invoke($extension, $action, $path);
        }

        return $result;
    }

    /**
     * @param array $services Services the user may access.
     * @return array
     */
    protected function allow(array $services): array
    {
        return [['role' => '*', 'service' => $services, 'action' => '*', 'method' => '*', 'allowed' => true]];
    }

    public function testRelationOfAForbiddenServiceIsRefused(): void
    {
        $this->assertSame(['Authors' => false], $this->access($this->allow(['articles']), ['Authors']));
    }

    public function testRelationOfAnAllowedServiceIsAccepted(): void
    {
        $this->assertSame(['Authors' => true], $this->access($this->allow(['articles', 'authors']), ['Authors']));
    }

    public function testRequestWithoutAuthorizationKeepsTheRelation(): void
    {
        $this->assertSame(['Authors' => true], $this->access(null, ['Authors']));
    }

    public function testUnknownAssociationIsLeftForTheQuery(): void
    {
        $this->assertSame(['Nope' => true], $this->access($this->allow(['articles']), ['Nope']));
    }

    public function testNestedServiceUsesTheRootRequest(): void
    {
        $this->assertSame(['Authors' => false], $this->access($this->allow(['articles']), ['Authors'], ['nested' => true]));
    }

    public function testServiceIsNamedAfterTheTableAlias(): void
    {
        // a prefixed database table does not rename the service
        TableRegistry::getTableLocator()->get('Articles')->getAssociation('Authors')->getTarget()->setTable('tbl_authors');
        $this->assertSame(['Authors' => true], $this->access($this->allow(['articles', 'authors']), ['Authors']));
    }

    public function testEveryStepOfADottedPathIsChecked(): void
    {
        $paths = ['Authors.Articles'];
        $this->assertSame(['Authors.Articles' => false], $this->access($this->allow(['authors']), $paths));
        $this->assertSame(['Authors.Articles' => true], $this->access($this->allow(['authors', 'articles']), $paths));
    }

    public function testPolicyWithoutAccessCheckKeepsTheRelation(): void
    {
        $policy = new class {
        };
        $this->assertSame(['Authors' => true], $this->access(null, ['Authors'], ['policy' => $policy]));
    }

    public function testCheckCanBeTurnedOff(): void
    {
        $result = $this->access($this->allow(['articles']), ['Authors'], ['config' => ['checkPermissions' => false]]);
        $this->assertSame(['Authors' => true], $result);
    }

    public function testAliasedAssociationChecksTheServiceOfItsTable(): void
    {
        // `Writers` holds Authors records, so the authors rules decide
        TableRegistry::getTableLocator()->get('Articles')
            ->belongsTo('Writers', ['className' => 'Authors', 'foreignKey' => 'author_id']);
        $denyAuthors = [
            ['role' => '*', 'service' => 'authors', 'action' => '*', 'method' => '*', 'allowed' => false],
            ['role' => '*', 'service' => '*', 'action' => '*', 'method' => '*', 'allowed' => true],
        ];
        $this->assertSame(['Writers' => false], $this->access($denyAuthors, ['Writers']));
        // the alias needs no rule of its own
        $this->assertSame(['Writers' => true], $this->access($this->allow(['articles', 'authors']), ['Writers']));
    }

    public function testCustomRbacAdapterIsLogged(): void
    {
        // a custom adapter does not know the target, so the relation is embedded as before
        Log::setConfig('relations_rbac_test', ['className' => 'Array', 'scopes' => ['api'], 'levels' => ['debug']]);
        $adapter = $this->createMock(RbacInterface::class);
        $adapter->method('checkPermissions')->willReturn(true);
        $result = $this->access($this->allow(['articles']), ['Authors'], ['rbac' => $adapter]);
        $logged = implode("\n", Log::engine('relations_rbac_test')->read());
        Log::drop('relations_rbac_test');
        $this->assertSame(['Authors' => true], $result);
        $this->assertStringContainsString('an RBAC adapter that may ignore the target', $logged);
    }
}
