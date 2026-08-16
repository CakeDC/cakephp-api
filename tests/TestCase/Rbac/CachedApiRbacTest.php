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

use Cake\Cache\Cache;
use CakeDC\Api\Rbac\CachedApiRbac;
use CakeDC\Api\Service\ServiceRegistry;
use CakeDC\Api\Test\ConfigTrait;
use CakeDC\Api\TestSuite\TestCase;

/**
 * Unit tests for the cached permissions-map based RBAC.
 */
class CachedApiRbacTest extends TestCase
{
    use ConfigTrait;

    protected function setUp(): void
    {
        parent::setUp();
        if (Cache::getConfig('_cakedc_api_auth_') === null) {
            Cache::setConfig('_cakedc_api_auth_', ['engine' => 'Array']);
        }
        Cache::clear('_cakedc_api_auth_');
    }

    protected function tearDown(): void
    {
        Cache::clear('_cakedc_api_auth_');
        ServiceRegistry::getServiceLocator()->clear();
        parent::tearDown();
    }

    /**
     * Build a service with a resolved action and attach it to the request.
     */
    protected function buildRbac(array $permissions): array
    {
        ServiceRegistry::getServiceLocator()->clear();
        $this->_initializeRequest([
            'params' => ['service' => 'articles'],
        ], 'GET');
        $service = ServiceRegistry::getServiceLocator()->get('articles', [
            'version' => null,
            'service' => 'articles',
            'request' => $this->request,
            'response' => $this->response,
            'baseUrl' => '/articles',
        ]);
        $service->dispatchPrepareAction();

        $this->request = $this->request->withAttribute('service', $service);

        $rbac = new CachedApiRbac([
            'api_permissions' => $permissions,
            'role_field' => 'role',
            'default_role' => 'user',
        ]);

        return [$rbac, $this->request];
    }

    public function testBuildPermissionsMapRoleServiceKeys(): void
    {
        $rbac = new CachedApiRbac([
            'api_permissions' => [
                ['role' => 'user', 'service' => 'articles', 'action' => 'index'],
                ['role' => 'admin', 'service' => '*', 'action' => '*'],
            ],
        ]);
        $map = $rbac->buildPermissionsMap();
        $this->assertArrayHasKey('user', $map);
        $this->assertArrayHasKey('articles', $map['user']);
        $this->assertArrayHasKey('admin', $map);
        $this->assertArrayHasKey('*', $map['admin']);
        $this->assertCount(1, $map['user']['articles']);
    }

    public function testBuildPermissionsMapServiceArray(): void
    {
        $rbac = new CachedApiRbac([
            'api_permissions' => [
                ['role' => 'user', 'service' => ['articles', 'posts'], 'action' => 'index'],
            ],
        ]);
        $map = $rbac->buildPermissionsMap();
        $this->assertArrayHasKey('articles', $map['user']);
        $this->assertArrayHasKey('posts', $map['user']);
    }

    public function testBuildPermissionsMapNoRoleDefaultsToWildcard(): void
    {
        $rbac = new CachedApiRbac([
            'api_permissions' => [
                ['service' => 'articles', 'action' => 'index'],
            ],
        ]);
        $map = $rbac->buildPermissionsMap();
        $this->assertArrayHasKey('*', $map);
        $this->assertArrayHasKey('articles', $map['*']);
    }

    public function testBuildPermissionsMapNoService(): void
    {
        $rbac = new CachedApiRbac([
            'api_permissions' => [
                ['role' => 'user', 'action' => 'index'],
            ],
        ]);
        $map = $rbac->buildPermissionsMap();
        $this->assertArrayHasKey('_', $map['user']);
    }

    public function testCheckPermissionsMatchingRoleAllows(): void
    {
        [$rbac, $request] = $this->buildRbac([
            ['role' => 'user', 'service' => 'articles', 'action' => 'index'],
        ]);
        $this->assertTrue($rbac->checkPermissions(['role' => 'user', 'id' => 1], $request));
    }

    public function testCheckPermissionsWrongRoleDenies(): void
    {
        [$rbac, $request] = $this->buildRbac([
            ['role' => 'admin', 'service' => 'articles', 'action' => 'index'],
        ]);
        $this->assertFalse($rbac->checkPermissions(['role' => 'user', 'id' => 1], $request));
    }

    public function testCheckPermissionsWildcardServiceAllows(): void
    {
        [$rbac, $request] = $this->buildRbac([
            ['role' => 'user', 'service' => '*', 'action' => 'index'],
        ]);
        $this->assertTrue($rbac->checkPermissions(['role' => 'user', 'id' => 1], $request));
    }

    public function testCheckPermissionsWildcardRoleAllows(): void
    {
        [$rbac, $request] = $this->buildRbac([
            ['role' => '*', 'service' => 'articles', 'action' => 'index'],
        ]);
        $this->assertTrue($rbac->checkPermissions(['role' => 'user', 'id' => 1], $request));
    }

    public function testCheckPermissionsWithoutServiceAttributeDenies(): void
    {
        $rbac = new CachedApiRbac([
            'api_permissions' => [
                ['role' => 'user', 'service' => 'articles', 'action' => 'index'],
            ],
        ]);
        $this->_initializeRequest([
            'params' => ['service' => 'articles'],
        ], 'GET');
        $this->assertFalse($rbac->checkPermissions(['role' => 'user', 'id' => 1], $this->request));
    }

    public function testCheckPermissionsUsesCachedMap(): void
    {
        [$rbac, $request] = $this->buildRbac([
            ['role' => 'user', 'service' => 'articles', 'action' => 'index'],
        ]);
        // second instance reads the permissions map from the cache engine
        $cached = new CachedApiRbac([
            'api_permissions' => [
                ['role' => 'user', 'service' => 'articles', 'action' => 'index'],
            ],
        ]);
        $this->assertTrue($cached->checkPermissions(['role' => 'user', 'id' => 1], $request));
    }

    public function testFileCacheEnginePersistsMap(): void
    {
        Cache::drop('_cakedc_api_auth_');
        Cache::setConfig('_cakedc_api_auth_', [
            'engine' => 'File',
            'path' => TMP . 'cache' . DS . 'rbac_file',
            'serialize' => true,
            'duration' => '+1 day',
        ]);
        Cache::clear('_cakedc_api_auth_');

        $permissions = [
            ['role' => 'user', 'service' => 'articles', 'action' => 'index'],
        ];
        $rbac = new CachedApiRbac([
            'api_permissions' => $permissions,
            'role_field' => 'role',
            'default_role' => 'user',
        ]);
        $map = $rbac->buildPermissionsMap();
        $this->assertArrayHasKey('user', $map);

        $cached = Cache::read('api_permissions_map', '_cakedc_api_auth_');
        $this->assertSame($map, $cached);

        Cache::drop('_cakedc_api_auth_');
        Cache::setConfig('_cakedc_api_auth_', ['engine' => 'Array']);
        Cache::clear('_cakedc_api_auth_');
    }
}
