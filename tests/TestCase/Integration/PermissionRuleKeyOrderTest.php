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

namespace CakeDC\Api\Test\TestCase\Integration;

use Cake\Core\Configure;
use CakeDC\Api\Test\ConfigTrait;
use CakeDC\Api\Test\Settings;
use CakeDC\Api\TestSuite\IntegrationTestCase;

/**
 * The `allowed` and `bypassAuth` keys end the check of a permission rule. A rule that starts with
 * one of them must still apply only to the services, actions and methods it names.
 */
class PermissionRuleKeyOrderTest extends IntegrationTestCase
{
    use ConfigTrait;

    public function setUp(): void
    {
        parent::setUp();
        Configure::write('App.fullBaseUrl', 'http://example.com');
        $this->_tokenAccess();
        $this->getDefaultUser(Settings::USER1);
    }

    public function tearDown(): void
    {
        parent::tearDown();
        Configure::delete('Test.Api.Permissions');
        Configure::delete('CakeDC/Auth.api_permissions');
    }

    /**
     * Replaces the permission rules for the next requests.
     *
     * @param array $rules Permission rules.
     * @return void
     */
    protected function setPermissions(array $rules): void
    {
        // the test app reads Test.Api.Permissions; lines without its permission file read the key directly
        Configure::write('Test.Api.Permissions', $rules);
        Configure::write('CakeDC/Auth.api_permissions', $rules);
    }

    public function testBypassAuthFirstAppliesOnlyToTheNamedService(): void
    {
        $this->setPermissions([
            ['bypassAuth' => true, 'role' => '*', 'service' => 'authors', 'action' => '*', 'method' => '*'],
        ]);
        $this->sendRequest('/articles', 'GET');
        $this->assertSame('error', $this->getJsonResponse()['status'] ?? null);

        $this->sendRequest('/authors', 'GET');
        $this->assertSuccess($this->getJsonResponse());
    }

    public function testAllowedFirstAppliesOnlyToTheNamedService(): void
    {
        $this->setPermissions([
            ['allowed' => true, 'role' => '*', 'service' => 'authors', 'action' => '*', 'method' => '*'],
        ]);
        $this->sendRequest('/articles', 'GET');
        $this->assertSame('error', $this->getJsonResponse()['status'] ?? null);

        $this->sendRequest('/authors', 'GET');
        $this->assertSuccess($this->getJsonResponse());
    }

    public function testAllowedFirstAppliesOnlyToTheNamedMethod(): void
    {
        $this->setPermissions([
            ['allowed' => true, 'role' => '*', 'service' => 'articles', 'action' => '*', 'method' => 'POST'],
        ]);
        $this->sendRequest('/articles', 'GET');
        $this->assertSame('error', $this->getJsonResponse()['status'] ?? null);
    }

    public function testRulesWithTheKeyLastBehaveAsBefore(): void
    {
        $this->setPermissions([
            ['role' => '*', 'service' => 'articles', 'action' => '*', 'method' => '*', 'allowed' => true],
        ]);
        $this->sendRequest('/articles', 'GET');
        $this->assertSuccess($this->getJsonResponse());

        $this->sendRequest('/authors', 'GET');
        $this->assertSame('error', $this->getJsonResponse()['status'] ?? null);
    }
}
