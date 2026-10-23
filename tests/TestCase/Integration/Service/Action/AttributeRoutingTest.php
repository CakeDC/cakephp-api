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

namespace CakeDC\Api\Test\TestCase\Integration\Service\Action;

use Cake\AttributeResolver\AttributeResolver;
use Cake\Core\Configure;
use CakeDC\Api\Test\ConfigTrait;
use CakeDC\Api\Test\Settings;
use CakeDC\Api\TestSuite\IntegrationTestCase;

/**
 * Proves attribute-declared service routes work end-to-end.
 */
class AttributeRoutingTest extends IntegrationTestCase
{
    use ConfigTrait;

    protected array $fixtures = [
        'plugin.CakeDC/Api.Attributes',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        Configure::write('App.fullBaseUrl', 'http://example.com');
        if (AttributeResolver::getConfig('default') !== null) {
            AttributeResolver::clear('default');
            AttributeResolver::drop('default');
        }
        AttributeResolver::setConfig('default', [
            'paths' => ['tests/App/Service/*.php', 'tests/App/Service/**/*.php'],
            'cache' => false,
        ]);
        $this->_tokenAccess();
        $this->getDefaultUser(Settings::USER1);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        AttributeResolver::drop('default');
    }

    public function testAttributeCustomActionRoute(): void
    {
        $this->sendRequest('/attributes/featured', 'GET', []);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $this->assertNotEmpty($result['data']);
        $this->assertResponseContains('First Attribute');
    }

    public function testApiResourceEnabledRoutes(): void
    {
        // index is enabled through ApiResource
        $this->sendRequest('/attributes', 'GET', []);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $this->assertNotEmpty($result['data']);
    }

    public function testApiResourceOnlyExcludesUnlistedRoutes(): void
    {
        // add is not listed in ApiResource(only: ...) -> route not found
        $this->sendRequest('/attributes', 'POST', ['title' => 'x']);
        $result = $this->getJsonResponse();
        $this->assertError($result, 404);
    }

    public function testAttributeRouteWithParams(): void
    {
        $this->sendRequest('/attributes/item/5', 'GET', []);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $this->assertSame('5', $result['data']['id']);
    }
}
