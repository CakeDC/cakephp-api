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

namespace CakeDC\Api\Test\TestCase\Integration\Service\Action\Extension;

use Cake\Core\Configure;
use Cake\Utility\Hash;
use CakeDC\Api\Test\ConfigTrait;
use CakeDC\Api\Test\Settings;
use CakeDC\Api\TestSuite\IntegrationTestCase;

/**
 * Class CrudRelationsExtensionTest
 *
 * @package CakeDC\Api\Test\TestCase\Integration\Service\Extension
 */
class CrudRelationsExtensionTest extends IntegrationTestCase
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
        Configure::write('App.fullBaseUrl', 'http://example.com');
        $this->_tokenAccess();
        $this->_loadDefaultExtensions('CakeDC/Api.CrudRelations');
        $this->_loadDefaultExtensions('CakeDC/Api.Paginate');
        $this->getDefaultUser(Settings::USER1);
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

    public function testNoInclude()
    {
        $this->sendRequest('/articles/1', 'GET', ['include_direct' => false]);
        $result = $this->getJsonResponse();
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

    public function testIncludeDirect()
    {
        $this->sendRequest('/articles/1', 'GET', ['include_direct' => true]);
        $result = $this->getJsonResponse();
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
                'last_name' => 'Cronos',
            ],
        ];
        $this->assertEquals($expected, Hash::get($result, 'data'));
    }

    public function testIncludeNoRelations()
    {
        $this->sendRequest('/articles/1', 'GET', ['include_relations' => '']);
        $result = $this->getJsonResponse();
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
        $result = $this->getJsonResponse();
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
                'last_name' => 'Cronos',
            ],
        ];
        $this->assertEquals($expected, Hash::get($result, 'data'));
    }
}
