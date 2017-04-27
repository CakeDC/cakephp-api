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
 * Class CrudAutocompleteListExtensionTest
 *
 * @package CakeDC\Api\Test\TestCase\Integration\Service\Extension
 */
class CrudAutocompleteListExtensionTest extends IntegrationTestCase
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
        $this->_loadDefaultExtensions('CakeDC/Api.CrudAutocompleteList');
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

    public function testNoAutocompleteList()
    {
        $this->sendRequest('/articles', 'GET', ['limit' => 4, 'autocomplete_list' => false]);
        $result = $this->responseJson();
        $this->assertSuccess($result);
        $this->assertEquals([1, 3, 2, 4], Hash::extract($result, 'data.{n}.author_id'));
        $this->assertEquals(['id', 'author_id', 'title', 'body', 'published'], array_keys(Hash::get($result, 'data.0')));
    }

    public function testAutocompleteList()
    {
        $this->sendRequest('/articles', 'GET', ['limit' => 4, 'autocomplete_list' => true]);
        $result = $this->responseJson();
        $this->assertSuccess($result);
        $this->assertEquals(['id', 'title'], array_keys(Hash::get($result, 'data.0')));
    }
}
