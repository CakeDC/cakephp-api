<?php
declare(strict_types=1);

/**
 * Copyright 2016 - 2026, Cake Development Corporation (https://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2016 - 2026, Cake Development Corporation (https://cakedc.com)
 * @license MIT License (https://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Api\Test\TestCase\Integration\Service\Action\Extension;

use Cake\Core\Configure;
use Cake\Utility\Hash;
use CakeDC\Api\Test\ConfigTrait;
use CakeDC\Api\Test\Settings;
use CakeDC\Api\TestSuite\IntegrationTestCase;

/**
 * CrudRelations embeds an association only when the user may access the related service.
 */
class CrudRelationsPermissionsTest extends IntegrationTestCase
{
    use ConfigTrait;

    public function setUp(): void
    {
        parent::setUp();
        Configure::write('App.fullBaseUrl', 'http://example.com');
        $this->_tokenAccess();
        $this->_loadDefaultExtensions('CakeDC/Api.CrudRelations');
        $this->getDefaultUser(Settings::USER1);
    }

    public function tearDown(): void
    {
        parent::tearDown();
        Configure::delete('Test.Api.Permissions');
        Configure::delete('CakeDC/Auth.api_permissions');
    }

    /**
     * Replaces the permission rules for the next requests.
     *
     * @param array $rules Permission rules.
     * @return void
     */
    protected function setPermissions(array $rules): void
    {
        // the test app reads Test.Api.Permissions; lines without its permission file read the key directly
        Configure::write('Test.Api.Permissions', $rules);
        Configure::write('CakeDC/Auth.api_permissions', $rules);
    }

    /**
     * @param array $services Services the user may access.
     * @return void
     */
    protected function allowServices(array $services): void
    {
        $this->setPermissions([
            ['role' => '*', 'service' => $services, 'action' => '*', 'method' => '*', 'allowed' => true],
        ]);
    }

    public function testRelationOfAForbiddenServiceIsLeftOut(): void
    {
        $this->allowServices(['articles']);
        $this->sendRequest('/articles/1', 'GET', ['include_relations' => 'authors']);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $this->assertSame('First Article', Hash::get($result, 'data.title'));
        $this->assertNull(Hash::get($result, 'data.author'));
    }

    public function testRelationOfAnAllowedServiceIsEmbedded(): void
    {
        $this->allowServices(['articles', 'authors']);
        $this->sendRequest('/articles/1', 'GET', ['include_relations' => 'authors']);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $this->assertSame('Electra', Hash::get($result, 'data.author.first_name'));
    }

    public function testDirectRelationsOfAForbiddenServiceAreLeftOut(): void
    {
        $this->allowServices(['articles']);
        $this->sendRequest('/articles', 'GET', ['include_direct' => 1]);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $this->assertNotEmpty($result['data']);
        $this->assertNull(Hash::get($result, 'data.0.author'));
    }

    public function testIndexOfAForbiddenRelationIsLeftOut(): void
    {
        $this->allowServices(['articles']);
        $this->sendRequest('/articles', 'GET', ['include_relations' => 'authors']);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $this->assertNull(Hash::get($result, 'data.0.author'));
    }

    public function testDirectRelationsOfAnAllowedServiceAreEmbedded(): void
    {
        $this->allowServices(['articles', 'authors']);
        $this->sendRequest('/articles', 'GET', ['include_direct' => 1]);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $this->assertNotNull(Hash::get($result, 'data.0.author'));
    }

    public function testNestedRouteChecksTheRelation(): void
    {
        // the inner service of a nested route keeps the request from before authorization
        $this->allowServices(['articles']);
        $this->sendRequest('/authors/1/articles', 'GET', ['include_relations' => 'authors']);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $this->assertNotEmpty($result['data']);
        $this->assertNull(Hash::get($result, 'data.0.author'));

        $this->allowServices(['articles', 'authors']);
        $this->sendRequest('/authors/1/articles', 'GET', ['include_relations' => 'authors']);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $this->assertNotNull(Hash::get($result, 'data.0.author'));
    }
}
