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

namespace CakeDC\Api\Test\TestCase\Rbac;

use Cake\Cache\Cache;
use Cake\Http\ServerRequest;
use CakeDC\Api\Rbac\ApiRbac;
use CakeDC\Api\Rbac\CachedApiRbac;
use CakeDC\Api\Service\Action\CrudAction;
use CakeDC\Api\Service\CrudService;
use CakeDC\Api\TestSuite\TestCase;

/**
 * The `allowed` and `bypassAuth` keys end the check of a rule; the other keys must still apply.
 */
class ApiRbacKeyOrderTest extends TestCase
{
    /**
     * @param string $service Service name.
     * @param string $action Action name.
     * @param string $method HTTP method.
     * @return \Cake\Http\ServerRequest
     */
    protected function request(string $service, string $action, string $method = 'GET'): ServerRequest
    {
        $serviceMock = $this->createMock(CrudService::class);
        $actionMock = $this->createMock(CrudAction::class);
        $actionMock->method('getName')->willReturn($action);
        $actionMock->method('getService')->willReturn($serviceMock);
        $serviceMock->method('getAction')->willReturn($actionMock);
        $serviceMock->method('getName')->willReturn($service);

        return (new ServerRequest(['environment' => ['REQUEST_METHOD' => $method]]))
            ->withAttribute('service', $serviceMock);
    }

    /**
     * @param array $rules Permission rules.
     * @param \Cake\Http\ServerRequest $request Request.
     * @return bool
     */
    protected function check(array $rules, ServerRequest $request): bool
    {
        $rbac = new ApiRbac();
        $rbac->setPermissions($rules);

        return $rbac->checkPermissions(['id' => 1, 'role' => 'user'], $request);
    }

    public function testBypassAuthFirstAppliesOnlyToTheNamedService(): void
    {
        $rules = [['bypassAuth' => true, 'role' => '*', 'service' => 'authors', 'action' => '*', 'method' => '*']];
        $this->assertFalse($this->check($rules, $this->request('articles', 'index')));
        $this->assertTrue($this->check($rules, $this->request('authors', 'index')));
    }

    public function testAllowedFirstAppliesOnlyToTheNamedActionAndMethod(): void
    {
        $rules = [['allowed' => true, 'role' => '*', 'service' => 'articles', 'action' => 'view', 'method' => 'GET']];
        $this->assertFalse($this->check($rules, $this->request('articles', 'delete', 'DELETE')));
        $this->assertFalse($this->check($rules, $this->request('articles', 'view', 'POST')));
        $this->assertTrue($this->check($rules, $this->request('articles', 'view')));
    }

    public function testAllowedFirstAppliesOnlyToTheNamedRole(): void
    {
        $rules = [['allowed' => true, 'role' => 'admin', 'service' => '*', 'action' => '*', 'method' => '*']];
        $this->assertFalse($this->check($rules, $this->request('articles', 'index')));
    }

    public function testRulesWithTheKeyLastBehaveAsBefore(): void
    {
        $rules = [['role' => '*', 'service' => 'articles', 'action' => '*', 'method' => '*', 'allowed' => true]];
        $this->assertTrue($this->check($rules, $this->request('articles', 'index')));
        $this->assertFalse($this->check($rules, $this->request('authors', 'index')));
    }

    public function testTargetAttributeIsCheckedInsteadOfTheAction(): void
    {
        $rules = [['role' => '*', 'service' => 'authors', 'action' => 'index', 'method' => '*', 'allowed' => true]];
        $request = $this->request('articles', 'view')
            ->withAttribute(ApiRbac::TARGET_ATTRIBUTE, ['service' => ['authors'], 'action' => 'index']);
        $this->assertTrue($this->check($rules, $request));
        $this->assertFalse($this->check($rules, $this->request('articles', 'view')));
    }

    public function testBypassAuthFalseDoesNotAllowAnonymousUsers(): void
    {
        $rbac = new ApiRbac();
        $rbac->setPermissions([['role' => '*', 'service' => '*', 'action' => '*', 'method' => '*', 'bypassAuth' => false]]);
        $this->assertFalse($rbac->checkPermissions([], $this->request('articles', 'index')));
        $this->assertTrue($rbac->checkPermissions(['id' => 1, 'role' => 'user'], $this->request('articles', 'index')));
    }

    public function testInvertedAllowedFirstAppliesOnlyToTheNamedService(): void
    {
        $rules = [['*allowed' => false, 'role' => '*', 'service' => 'authors', 'action' => '*', 'method' => '*']];
        $this->assertFalse($this->check($rules, $this->request('articles', 'index')));
        $this->assertTrue($this->check($rules, $this->request('authors', 'index')));
    }

    public function testCachedRbacChecksTheTarget(): void
    {
        if (!class_exists(CachedApiRbac::class)) {
            $this->markTestSkipped('No CachedApiRbac on this line');
        }
        // CachedApiRbac keeps its rule map in this cache config
        if (!Cache::getConfig('_cakedc_api_auth_')) {
            Cache::setConfig('_cakedc_api_auth_', ['className' => 'Array']);
        }
        Cache::clear('_cakedc_api_auth_');
        $rules = [['role' => '*', 'service' => 'authors', 'action' => 'index', 'method' => '*', 'allowed' => true]];
        $rbac = new CachedApiRbac(['api_permissions' => $rules]);
        Cache::clear('_cakedc_api_auth_');
        $request = $this->request('articles', 'view')
            ->withAttribute(ApiRbac::TARGET_ATTRIBUTE, ['service' => ['authors'], 'action' => 'index']);
        $this->assertTrue($rbac->checkPermissions(['id' => 1, 'role' => 'user'], $request));
        $this->assertFalse($rbac->checkPermissions(['id' => 1, 'role' => 'user'], $this->request('articles', 'view')));
    }
}
