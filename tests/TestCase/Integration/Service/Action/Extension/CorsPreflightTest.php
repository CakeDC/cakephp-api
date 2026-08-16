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

namespace CakeDC\Api\Test\TestCase\Integration\Service\Action\Extension;

use Cake\Core\Configure;
use CakeDC\Api\Test\ConfigTrait;
use CakeDC\Api\Test\Settings;
use CakeDC\Api\TestSuite\IntegrationTestCase;

/**
 * CORS preflight (OPTIONS) request checks.
 */
class CorsPreflightTest extends IntegrationTestCase
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
        $this->_loadDefaultExtensions('CakeDC/Api.Cors');
        $this->getDefaultUser(Settings::USER1);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Configure::write('Test.Api.Extension');
    }

    public function testPreflightOptions(): void
    {
        $this->_request['headers']['Origin'] = 'http://foobar.com';
        $this->_request['headers']['Access-Control-Request-Method'] = 'POST';
        $this->sendRequest('/posts', 'OPTIONS', []);
        $headers = $this->_response->getHeaders();
        $this->assertEquals(['*'], $headers['Access-Control-Allow-Origin']);
        $this->assertEquals(['true'], $headers['Access-Control-Allow-Credentials']);
    }
}
