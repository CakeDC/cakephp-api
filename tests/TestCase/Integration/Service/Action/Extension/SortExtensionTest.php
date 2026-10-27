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
 * Class SortExtensionTest
 *
 * @package CakeDC\Api\Test\TestCase\Integration\Service\Extension
 */
class SortExtensionTest extends IntegrationTestCase
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
        $this->_loadDefaultExtensions('CakeDC/Api.Sort');
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

    public function testDefault()
    {
        $this->sendRequest('/articles', 'GET');
        $result = $this->getJsonResponse();
        $expected = [
            'page' => 1,
            'limit' => 20,
            'pages' => 1,
            'count' => 15,
        ];
        $this->assertSuccess($result);
        $this->assertEquals($expected, $result['pagination']);
    }

    public function testSortById()
    {
        $this->sendRequest('/authors', 'GET', ['limit' => 4, 'sort' => 'id']);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $this->assertEquals(range(1, 4), Hash::extract($result, 'data.{n}.id'));
    }

    public function testSortByIdDesc()
    {
        $this->sendRequest('/authors', 'GET', ['limit' => 4, 'sort' => 'id', 'direction' => 'desc']);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $this->assertEquals(array_reverse(range(12, 15)), Hash::extract($result, 'data.{n}.id'));
    }

    public function testSortByName()
    {
        $this->sendRequest('/authors', 'GET', ['limit' => 4, 'sort' => 'first_name']);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $this->assertEquals([7, 5, 11, 15], Hash::extract($result, 'data.{n}.id'));
    }

    public function testSortByNameDesc()
    {
        $this->sendRequest('/authors', 'GET', ['limit' => 4, 'sort' => 'first_name', 'direction' => 'desc']);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $this->assertEquals([4, 9, 14, 10], Hash::extract($result, 'data.{n}.id'));
    }

    public function testSortByAliasedField()
    {
        $this->sendRequest('/authors', 'GET', ['limit' => 4, 'sort' => 'Authors.id', 'direction' => 'desc']);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $this->assertEquals(array_reverse(range(12, 15)), Hash::extract($result, 'data.{n}.id'));
    }

    public function testUnknownSortFieldIsIgnored()
    {
        $this->sendRequest('/authors', 'GET', ['limit' => 4, 'sort' => 'unknown_field']);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $this->assertEquals(range(1, 4), Hash::extract($result, 'data.{n}.id'));
    }

    public function testSqlExpressionSortIsIgnored()
    {
        $this->sendRequest('/authors', 'GET', ['limit' => 4, 'sort' => '(CASE WHEN 1=1 THEN first_name END)']);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $this->assertEquals(range(1, 4), Hash::extract($result, 'data.{n}.id'));
    }

    public function testInvalidDirectionFallsBackToAsc()
    {
        $this->sendRequest('/authors', 'GET', ['limit' => 4, 'sort' => 'last_name', 'direction' => 'foo']);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $this->assertEquals([9, 3, 7, 1], Hash::extract($result, 'data.{n}.id'));
    }

    public function testUppercaseDirection()
    {
        $this->sendRequest('/authors', 'GET', ['limit' => 4, 'sort' => 'id', 'direction' => 'DESC']);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $this->assertEquals(array_reverse(range(12, 15)), Hash::extract($result, 'data.{n}.id'));
    }

    public function testArraySortParamIsIgnored()
    {
        // sent as a raw query string, because sendRequest() drops array values from $data
        $this->sendRequest('/authors?sort[]=first_name&direction[]=desc', 'GET', ['limit' => 4]);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $this->assertEquals(range(1, 4), Hash::extract($result, 'data.{n}.id'));
    }

    public function testOtherTableAliasSortIsIgnored()
    {
        $this->sendRequest('/authors', 'GET', ['limit' => 4, 'sort' => 'Articles.id', 'direction' => 'desc']);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $this->assertEquals(range(1, 4), Hash::extract($result, 'data.{n}.id'));
    }

    public function testSortableFields()
    {
        $this->_addSettingByPath('Service.authors.Action.Index.Extension', [
            'CakeDC/Api.Sort' => ['sortableFields' => ['Authors.last_name']],
        ]);
        $this->sendRequest('/authors', 'GET', ['limit' => 4, 'sort' => 'last_name']);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $this->assertEquals([9, 3, 7, 1], Hash::extract($result, 'data.{n}.id'));

        // first_name is not whitelisted, so it is ignored
        $this->sendRequest('/authors', 'GET', ['limit' => 4, 'sort' => 'first_name']);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $this->assertEquals(range(1, 4), Hash::extract($result, 'data.{n}.id'));
    }

    public function testSortableFieldsMatchAliasedRequestField()
    {
        $this->_addSettingByPath('Service.authors.Action.Index.Extension', [
            'CakeDC/Api.Sort' => ['sortableFields' => ['last_name']],
        ]);
        $this->sendRequest('/authors', 'GET', ['limit' => 4, 'sort' => 'Authors.last_name']);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $this->assertEquals([9, 3, 7, 1], Hash::extract($result, 'data.{n}.id'));
    }

    public function testSortableOwnFieldIsQualifiedWithJoinedAssociation()
    {
        $this->_loadDefaultExtensions('CakeDC/Api.CrudRelations');
        $this->_addSettingByPath('Service.articles.Action.Index.Extension', [
            'CakeDC/Api.Sort' => ['sortableFields' => ['id']],
        ]);
        $this->sendRequest('/articles', 'GET', ['limit' => 4, 'sort' => 'id', 'direction' => 'desc', 'include_relations' => 'authors']);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $this->assertEquals([15, 14, 13, 12], Hash::extract($result, 'data.{n}.id'));
    }

    public function testSortableAssociationField()
    {
        $this->_loadDefaultExtensions('CakeDC/Api.CrudRelations');
        $this->_addSettingByPath('Service.articles.Action.Index.Extension', [
            'CakeDC/Api.Sort' => ['sortableFields' => ['Authors.first_name']],
        ]);
        // limited to 2: only Alex (article 5) and Alien (article 12) are unambiguous
        // without a tie-breaker, the next four articles all belong to "Electra"
        $this->sendRequest('/articles', 'GET', ['limit' => 2, 'sort' => 'Authors.first_name', 'include_relations' => 'authors']);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $this->assertEquals([5, 12], Hash::extract($result, 'data.{n}.id'));
    }
}
