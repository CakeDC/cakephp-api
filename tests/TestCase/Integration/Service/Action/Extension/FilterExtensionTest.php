<?php
/**
 * Copyright 2016 - 2018, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2016 - 2018, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Api\Test\TestCase\Integration\Service\Action\Extension;

use CakeDC\Api\TestSuite\IntegrationTestCase;
use CakeDC\Api\Test\ConfigTrait;
use CakeDC\Api\Test\FixturesTrait;
use CakeDC\Api\Test\Settings;
use Cake\Core\Configure;
use Cake\Utility\Hash;

/**
 * Class FilterExtensionTest
 *
 * @package CakeDC\Api\Test\TestCase\Integration\Service\Extension
 */
class FilterExtensionTest extends IntegrationTestCase
{

    use ConfigTrait;
    use FixturesTrait;

    /**
     * setUp
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        Configure::write('App.fullBaseUrl', 'http://example.com');
        $this->_tokenAccess();
        $this->_loadDefaultExtensions('CakeDC/Api.Filter');
        $this->_loadDefaultExtensions('CakeDC/Api.Paginate');
        $this->getDefaultUser(Settings::USER1);
    }

    /**
     * tearDown
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        Configure::write('Test.Api.Extension', null);
    }

    public function testFilterByFields()
    {
        $this->sendRequest('/articles', 'GET', ['limit' => 4, 'title' => 'Article N4']);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $this->assertEquals([4], Hash::extract($result, 'data.{n}.id'));

        $this->sendRequest('/articles', 'GET', ['limit' => 4, 'title$like' => 'Article N']);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $this->assertEquals([4, 5, 6, 7], Hash::extract($result, 'data.{n}.id'));

        $this->sendRequest('/articles', 'GET', ['limit' => 4, 'id$gt' => '5', 'id$lt' => 7]);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $this->assertEquals([6], Hash::extract($result, 'data.{n}.id'));
    }
}
