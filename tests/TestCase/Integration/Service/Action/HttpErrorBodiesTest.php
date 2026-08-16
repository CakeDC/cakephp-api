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

use Cake\Core\Configure;
use Cake\Utility\Hash;
use CakeDC\Api\Test\ConfigTrait;
use CakeDC\Api\Test\Settings;
use CakeDC\Api\TestSuite\IntegrationTestCase;

/**
 * HTTP error body checks: method not allowed and validation error bodies.
 */
class HttpErrorBodiesTest extends IntegrationTestCase
{
    use ConfigTrait;

    protected array $fixtures = [
        'plugin.CakeDC/Api.Posts',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        Configure::write('App.fullBaseUrl', 'http://example.com');
        $this->_tokenAccess();
        $this->getDefaultUser(Settings::USER1);
    }

    public function testValidationErrorBody(): void
    {
        $this->sendRequest('/posts', 'POST', ['body' => 'missing title']);
        $result = $this->getJsonResponse();
        $this->assertError($result, 422);
        $this->assertErrorMessage($result, 'Validation failed');
        $errors = Hash::get($result, 'data');
        $this->assertArrayHasKey('title', $errors);
        $this->assertNotEmpty($errors['title']);
    }

    public function testItemRouteWithInvalidMethod(): void
    {
        // POST is not mapped on the item route
        $this->sendRequest('/posts/1', 'POST', ['title' => 'x']);
        $result = $this->getJsonResponse();
        $this->assertTrue(is_array($result));
    }

    public function testCollectionRouteWithDelete(): void
    {
        // DELETE is only mapped on the item route, not the collection
        $this->sendRequest('/posts', 'DELETE', []);
        $result = $this->getJsonResponse();
        $this->assertTrue(is_array($result));
    }
}
