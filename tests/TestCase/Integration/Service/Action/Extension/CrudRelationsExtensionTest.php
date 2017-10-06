<?php
/**
 * Copyright 2016 - 2017, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2016 - 2017, Cake Development Corporation (http://cakedc.com)
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
 * Class CrudRelationsExtensionTest
 *
 * @package CakeDC\Api\Test\TestCase\Integration\Service\Extension
 */
class CrudRelationsExtensionTest extends IntegrationTestCase
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
        $this->_loadDefaultExtensions('CakeDC/Api.CrudRelations');
        $this->_loadDefaultExtensions('CakeDC/Api.Paginate');
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
        Configure::write('Test.Api.Extension', null);
    }

    public function testNoInclude()
    {
        $this->sendRequest('/articles/1', 'GET', ['include_direct' => false]);
        $result = $this->responseJson();
        $this->assertSuccess($result);
        $expected = [
            'id' => 1,
            'author_id' => 1,
            'title' => 'First Article',
            'body' => 'First Article Body',
            'published' => 'Y'
        ];
        $this->assertEquals($expected, Hash::get($result, 'data'));
    }

    public function testIncludeDirect()
    {
        $this->sendRequest('/articles/1', 'GET', ['include_direct' => true]);
        $result = $this->responseJson();
        $this->assertSuccess($result);
        $expected = [
            'id' => 1,
            'author_id' => 1,
            'title' => 'First Article',
            'body' => 'First Article Body',
            'published' => 'Y',
            'author' => [
                'id' => 1,
                'first_name' => 'Electra',
                'last_name' => 'Cronos'
            ]
        ];
        $this->assertEquals($expected, Hash::get($result, 'data'));
    }

    public function testIncludeNoRelations()
    {
        $this->sendRequest('/articles/1', 'GET', ['include_relations' => '']);
        $result = $this->responseJson();
        $this->assertSuccess($result);
        $expected = [
            'id' => 1,
            'author_id' => 1,
            'title' => 'First Article',
            'body' => 'First Article Body',
            'published' => 'Y',
        ];
        $this->assertEquals($expected, Hash::get($result, 'data'));
    }

    public function testIncludeAuthorRelations()
    {
        $this->sendRequest('/articles/1', 'GET', ['include_relations' => 'authors']);
        $result = $this->responseJson();
        $this->assertSuccess($result);
        $expected = [
            'id' => 1,
            'author_id' => 1,
            'title' => 'First Article',
            'body' => 'First Article Body',
            'published' => 'Y',
            'author' => [
                'id' => 1,
                'first_name' => 'Electra',
                'last_name' => 'Cronos'
            ]
        ];
        $this->assertEquals($expected, Hash::get($result, 'data'));
    }
}
