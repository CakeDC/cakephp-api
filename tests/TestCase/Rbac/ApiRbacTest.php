<?php
declare(strict_types=1);

/**
 * Copyright 2016 - 2026, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2016 - 2026, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Api\Test\TestCase\Rbac;

use CakeDC\Api\Rbac\ApiRbac;
use CakeDC\Api\Service\ServiceRegistry;
use CakeDC\Api\Test\ConfigTrait;
use CakeDC\Api\TestSuite\TestCase;

/**
 * Unit tests for the RBAC permission matching logic.
 */
class ApiRbacTest extends TestCase
{
    use ConfigTrait;

    protected function tearDown(): void
    {
        ServiceRegistry::getServiceLocator()->clear();
        parent::tearDown();
    }

    /**
     * Build a service with a resolved action and attach it to the request.
     */
    protected function buildRequest(?array $permissions, string $method = 'GET', array $user = ['role' => 'user', 'id' => 1]): array
    {
        ServiceRegistry::getServiceLocator()->clear();
        $this->_initializeRequest([
            'params' => ['service' => 'articles'],
        ], $method);
        $service = ServiceRegistry::getServiceLocator()->get('articles', [
            'version' => null,
            'service' => 'articles',
            'request' => $this->request,
            'response' => $this->response,
            'baseUrl' => '/articles',
        ]);
        $service->dispatchPrepareAction();

        $this->request = $this->request->withAttribute('service', $service);

        $rbac = new ApiRbac([
            'api_permissions' => $permissions,
            'role_field' => 'role',
            'default_role' => 'user',
        ]);

        return [$rbac, $this->request, $user];
    }

    public function testMissingServiceOrActionIsDenied(): void
    {
        [$rbac, $request, $user] = $this->buildRequest([
            ['role' => 'user'],
        ]);
        $this->assertFalse($rbac->checkPermissions($user, $request));
    }

    public function testRoleMatchAllows(): void
    {
        [$rbac, $request, $user] = $this->buildRequest([
            ['role' => 'admin', 'service' => '*', 'action' => '*'],
        ], 'GET', ['role' => 'admin', 'id' => 1]);
        $this->assertTrue($rbac->checkPermissions($user, $request));
    }

    public function testRoleMismatchDenies(): void
    {
        [$rbac, $request, $user] = $this->buildRequest([
            ['role' => 'admin', 'service' => '*', 'action' => '*'],
        ]);
        $this->assertFalse($rbac->checkPermissions($user, $request));
    }

    public function testExactServiceActionAllows(): void
    {
        [$rbac, $request, $user] = $this->buildRequest([
            ['role' => 'user', 'service' => 'articles', 'action' => 'index'],
        ]);
        $this->assertTrue($rbac->checkPermissions($user, $request));
    }

    public function testExactServiceActionMismatchDenies(): void
    {
        [$rbac, $request, $user] = $this->buildRequest([
            ['role' => 'user', 'service' => 'articles', 'action' => 'edit'],
        ]);
        $this->assertFalse($rbac->checkPermissions($user, $request));
    }

    public function testMethodMatch(): void
    {
        [$rbac, $request, $user] = $this->buildRequest([
            ['role' => 'user', 'service' => 'articles', 'action' => 'index', 'method' => 'GET'],
        ], 'GET');
        $this->assertTrue($rbac->checkPermissions($user, $request));

        [$rbac, $request, $user] = $this->buildRequest([
            ['role' => 'user', 'service' => 'articles', 'action' => 'index', 'method' => 'GET'],
        ], 'POST');
        $this->assertFalse($rbac->checkPermissions($user, $request));
    }

    public function testBypassAuthAllows(): void
    {
        [$rbac, $request, $user] = $this->buildRequest([
            ['role' => 'user', 'service' => 'articles', 'action' => 'index', 'bypassAuth' => true],
        ]);
        $this->assertTrue($rbac->checkPermissions($user, $request));
    }

    public function testAllowedFalseDenies(): void
    {
        [$rbac, $request, $user] = $this->buildRequest([
            ['role' => 'user', 'service' => 'articles', 'action' => 'index', 'allowed' => false],
        ]);
        $this->assertFalse($rbac->checkPermissions($user, $request));
    }

    public function testCallableRule(): void
    {
        [$rbac, $request, $user] = $this->buildRequest([
            ['role' => 'user', 'service' => 'articles', 'action' => 'index', 'rule' => fn(): true => true],
        ]);
        $this->assertTrue($rbac->checkPermissions($user, $request));

        [$rbac, $request, $user] = $this->buildRequest([
            ['role' => 'user', 'service' => 'articles', 'action' => 'index', 'rule' => fn(): false => false],
        ]);
        $this->assertFalse($rbac->checkPermissions($user, $request));
    }

    public function testRuleObject(): void
    {
        [$rbac, $request, $user] = $this->buildRequest([
            ['role' => 'user', 'service' => 'articles', 'action' => 'index', 'rule' => new AlwaysTrueRule()],
        ]);
        $this->assertTrue($rbac->checkPermissions($user, $request));

        [$rbac, $request, $user] = $this->buildRequest([
            ['role' => 'user', 'service' => 'articles', 'action' => 'index', 'rule' => new AlwaysFalseRule()],
        ]);
        $this->assertFalse($rbac->checkPermissions($user, $request));
    }

    public function testClassNameRule(): void
    {
        [$rbac, $request, $user] = $this->buildRequest([
            ['role' => 'user', 'service' => 'articles', 'action' => 'index', 'rule' => ['className' => AlwaysTrueRule::class]],
        ]);
        $this->assertTrue($rbac->checkPermissions($user, $request));

        [$rbac, $request, $user] = $this->buildRequest([
            ['role' => 'user', 'service' => 'articles', 'action' => 'index', 'rule' => ['className' => AlwaysFalseRule::class]],
        ]);
        $this->assertFalse($rbac->checkPermissions($user, $request));
    }

    public function testUserFieldMatch(): void
    {
        [$rbac, $request, $user] = $this->buildRequest([
            ['role' => 'user', 'service' => 'articles', 'action' => 'index', 'id' => 1],
        ], 'GET', ['role' => 'user', 'id' => 1]);
        $this->assertTrue($rbac->checkPermissions($user, $request));

        [$rbac, $request, $user] = $this->buildRequest([
            ['role' => 'user', 'service' => 'articles', 'action' => 'index', 'id' => 2],
        ], 'GET', ['role' => 'user', 'id' => 1]);
        $this->assertFalse($rbac->checkPermissions($user, $request));
    }

    public function testInverseWildcardDenies(): void
    {
        // '*service' with the actual service matches the negation → deny
        [$rbac, $request, $user] = $this->buildRequest([
            ['role' => 'user', '*service' => 'articles', 'action' => 'index'],
        ]);
        $this->assertFalse($rbac->checkPermissions($user, $request));
    }

    public function testWildcardServiceAllows(): void
    {
        [$rbac, $request, $user] = $this->buildRequest([
            ['role' => 'user', 'service' => '*', 'action' => 'index'],
        ]);
        $this->assertTrue($rbac->checkPermissions($user, $request));
    }

    public function testRouteBreakingCharsCamelize(): void
    {
        ServiceRegistry::getServiceLocator()->clear();
        $this->_initializeRequest([
            'params' => ['service' => 'articles', 'action' => 'my_action'],
        ], 'GET');
        $service = ServiceRegistry::getServiceLocator()->get('articles', [
            'version' => null,
            'service' => 'articles',
            'request' => $this->request,
            'response' => $this->response,
            'baseUrl' => '/articles',
        ]);
        $service->dispatchPrepareAction();
        $service->getAction()->setName('my_action');
        $this->request = $this->request->withAttribute('service', $service);

        $rbac = new ApiRbac([
            'api_permissions' => [
                ['role' => 'user', 'service' => 'articles', 'action' => 'MyAction'],
            ],
        ]);
        $this->assertTrue($rbac->checkPermissions(['role' => 'user', 'id' => 1], $this->request));
    }

    public function testDefaultRoleFallback(): void
    {
        [$rbac, $request, $user] = $this->buildRequest([
            ['role' => 'user', 'service' => 'articles', 'action' => 'index'],
        ], 'GET', ['id' => 1]); // no role field → default_role 'user'
        $this->assertTrue($rbac->checkPermissions($user, $request));
    }

    public function testNoPermissionsDenies(): void
    {
        [$rbac, $request, $user] = $this->buildRequest([]);
        $this->assertFalse($rbac->checkPermissions($user, $request));
    }

    public function testIllegalUserKeyDenies(): void
    {
        [$rbac, $request, $user] = $this->buildRequest([
            ['role' => 'user', 'user' => ['id' => 1], 'service' => 'articles', 'action' => 'index'],
        ]);
        $this->assertFalse($rbac->checkPermissions($user, $request));
    }
}
