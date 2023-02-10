<?php
declare(strict_types=1);

/**
 * Copyright 2016 - 2019, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2016 - 2019, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Api\Test\TestCase\Integration\Service\Action;

use Cake\Core\Configure;
use Cake\Utility\Hash;
use CakeDC\Api\Test\ConfigTrait;
use CakeDC\Api\TestSuite\IntegrationTestCase;

/**
 * Class LoginActionTest
 *
 * @package CakeDC\Api\Test\TestCase\Integration\Service\Action\Auth
 */
class ArticlesDataActionTest extends IntegrationTestCase
{
    use ConfigTrait;

    /**
     * setUp
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->_authAccess();
        Configure::write('App.fullBaseUrl', 'http://example.com');
    }

    /**
     * tearDown
     *
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();
        Configure::write('Test.Api.Extension', null);
    }

    public function testData()
    {
        $this->sendRequest('/articles/data', 'GET', []);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $expected = [
            'a' => 1,
            'b' => 2,
        ];
        $data = Hash::get($result, 'data');
        $this->assertEquals($expected, $data);
    }
}
