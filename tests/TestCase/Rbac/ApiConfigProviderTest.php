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

use Cake\Core\Configure;
use CakeDC\Api\Rbac\Permissions\ApiConfigProvider;
use CakeDC\Api\TestSuite\TestCase;

/**
 * Unit tests for the ApiConfigProvider permissions loader.
 */
class ApiConfigProviderTest extends TestCase
{
    protected function tearDown(): void
    {
        Configure::delete('CakeDC/Auth');
        parent::tearDown();
    }

    public function testGetPermissionsLoadsFromConfigFile(): void
    {
        $provider = new ApiConfigProvider();
        $permissions = $provider->getPermissions();
        $this->assertCount(2, $permissions);
        $this->assertSame('*', $permissions[0]['role']);
        $this->assertSame('login', $permissions[1]['action']);
        $this->assertTrue($permissions[1]['bypassAuth']);
    }

    public function testGetPermissionsWithDisabledAutoload(): void
    {
        $provider = new ApiConfigProvider(['autoload_config' => null]);
        $this->assertSame($provider->getDefaultPermissions(), $provider->getPermissions());
    }

    public function testGetPermissionsWithMissingConfigFallsBackToDefaults(): void
    {
        $provider = new ApiConfigProvider(['autoload_config' => 'non_existent_permissions_file']);
        $this->assertSame($provider->getDefaultPermissions(), $provider->getPermissions());
    }

    public function testCustomDefaultPermissions(): void
    {
        $provider = new ApiConfigProvider(['autoload_config' => null]);
        $defaults = [
            ['role' => 'user', 'service' => 'articles', 'action' => 'index'],
        ];
        $provider->setDefaultPermissions($defaults);
        $this->assertSame($defaults, $provider->getDefaultPermissions());
        $this->assertSame($defaults, $provider->getPermissions());
    }
}
