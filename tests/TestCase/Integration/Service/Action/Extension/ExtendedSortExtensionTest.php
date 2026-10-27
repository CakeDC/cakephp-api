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

class ExtendedSortExtensionTest extends IntegrationTestCase
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
        $this->_loadDefaultExtensions('CakeDC/Api.ExtendedSort');
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
        $this->sendRequest('/authors', 'GET', ['limit' => 4, 'sort' => json_encode(['id' => 'asc'], JSON_THROW_ON_ERROR)]);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $this->assertEquals(range(1, 4), Hash::extract($result, 'data.{n}.id'));
    }

    public function testSortByIdDesc()
    {
        $this->sendRequest('/authors', 'GET', ['limit' => 4, 'sort' => json_encode(['id' => 'desc'], JSON_THROW_ON_ERROR)]);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $this->assertEquals(array_reverse(range(12, 15)), Hash::extract($result, 'data.{n}.id'));
    }

    public function testSortByName()
    {
        $this->sendRequest('/authors', 'GET', ['limit' => 4, 'sort' => json_encode(['first_name' => 'asc'], JSON_THROW_ON_ERROR)]);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $this->assertEquals([7, 5, 11, 15], Hash::extract($result, 'data.{n}.id'));

        $this->sendRequest('/authors', 'GET', ['limit' => 4, 'sort' => json_encode(['first_name' => 'asc', 'last_name' => 'asc'], JSON_THROW_ON_ERROR)]);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $this->assertEquals([7, 5, 11, 15], Hash::extract($result, 'data.{n}.id'));
    }

    public function testSortByFirstNameDesc()
    {
        $this->sendRequest('/authors', 'GET', ['limit' => 4, 'sort' => json_encode(['first_name' => 'desc'], JSON_THROW_ON_ERROR)]);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $this->assertEquals([4, 9, 14, 10], Hash::extract($result, 'data.{n}.id'));
    }

    public function testInvalidJsonIsIgnored()
    {
        $this->sendRequest('/authors', 'GET', ['limit' => 4, 'sort' => '{not json']);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $this->assertEquals(range(1, 4), Hash::extract($result, 'data.{n}.id'));
    }

    public function testInvalidEntriesAreIgnored()
    {
        $sort = [
            '(CASE WHEN 1=1 THEN first_name END)' => 'asc',
            'unknown_field' => 'asc',
            'Authors.last_name' => 'foo',
            'id' => ['desc'],
        ];
        // only Authors.last_name survives: the SQL expression and the unknown field are
        // rejected, its own invalid direction is normalized to asc, and the non-string
        // direction of `id` drops that entry
        $this->sendRequest('/authors', 'GET', ['limit' => 4, 'sort' => json_encode($sort, JSON_THROW_ON_ERROR)]);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $this->assertEquals([9, 3, 7, 1], Hash::extract($result, 'data.{n}.id'));
    }

    public function testSortableFields()
    {
        $this->_addSettingByPath('Service.authors.Action.Index.Extension', [
            'CakeDC/Api.ExtendedSort' => ['sortableFields' => ['Authors.last_name']],
        ]);
        $sort = ['first_name' => 'desc', 'last_name' => 'asc'];
        $this->sendRequest('/authors', 'GET', ['limit' => 4, 'sort' => json_encode($sort, JSON_THROW_ON_ERROR)]);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $this->assertEquals([9, 3, 7, 1], Hash::extract($result, 'data.{n}.id'));
    }

    public function testListAndNumericKeyEntriesAreIgnored()
    {
        // a numeric key makes CakePHP emit the value verbatim, so list elements and numeric
        // object keys are rejected, including the formerly accepted list form ["first_name"]
        $payloads = [
            '["(CASE WHEN 1=1 THEN first_name END)"]',
            '{"0": "(CASE WHEN 1=1 THEN first_name END)"}',
            '["first_name"]',
        ];
        foreach ($payloads as $sort) {
            $this->sendRequest('/authors', 'GET', ['limit' => 4, 'sort' => $sort]);
            $result = $this->getJsonResponse();
            $this->assertSuccess($result);
            $this->assertEquals(range(1, 4), Hash::extract($result, 'data.{n}.id'), $sort);
        }
    }

    public function testUnlistedAssociationFieldIsIgnored()
    {
        $this->_loadDefaultExtensions('CakeDC/Api.CrudRelations');
        $sort = json_encode(['Authors.first_name' => 'asc'], JSON_THROW_ON_ERROR);
        $this->sendRequest('/articles', 'GET', ['limit' => 2, 'sort' => $sort, 'include_relations' => 'authors']);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $this->assertEquals([1, 2], Hash::extract($result, 'data.{n}.id'));
    }
}
