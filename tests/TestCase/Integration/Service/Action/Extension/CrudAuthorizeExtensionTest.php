<?php
/**
 * Copyright 2016, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2016, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Api\Test\TestCase\Integration\Service\Action\Extension;

use CakeDC\Api\TestSuite\IntegrationTestCase;
use CakeDC\Api\Test\ConfigTrait;
use CakeDC\Api\Test\FixturesTrait;
use CakeDC\Api\Test\Settings;
use Cake\Core\Configure;
use Cake\Utility\Hash;

class CrudAuthorizeExtensionTest extends IntegrationTestCase
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
        $this->_loadDefaultExtensions([]);
        // setup permissions in test actions
        $this->defaultUser(Settings::USER1);
    }


    /**
     * tearDown
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
    }

    public function testDefault()
    {
        $this->sendRequest('/articles', 'GET');
        $result = $this->responseJson();
        // $expected = [];
        $this->assertSuccess($result);
        // $this->assertEquals($expected, $result);
    }
}
