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
use CakeDC\Api\Test\FixturesTrait;
use CakeDC\Api\Test\Settings;
use CakeDC\Api\TestSuite\IntegrationTestCase;

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
    public function setUp(): void
    {
        parent::setUp();
        Configure::write('App.fullBaseUrl', 'http://example.com');
        $this->_tokenAccess();
        $this->_loadDefaultExtensions('CakeDC/Api.CrudAutocompleteList');
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

    public function testNoAutocompleteList()
    {
        $this->sendRequest('/articles', 'GET', ['limit' => 4, 'autocomplete_list' => false]);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $this->assertEquals([1, 3, 2, 4], Hash::extract($result, 'data.{n}.author_id'));
        $this->assertEquals(['id', 'author_id', 'title', 'body', 'published'], array_keys(Hash::get($result, 'data.0')));
    }

    public function testAutocompleteList()
    {
        $this->sendRequest('/articles', 'GET', ['limit' => 4, 'autocomplete_list' => true]);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $this->assertEquals(['id', 'title'], array_keys(Hash::get($result, 'data.0')));
    }
}
