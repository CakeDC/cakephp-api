<?php
declare(strict_types=1);

/**
 * Copyright 2016 - 2026, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2016 - 2026, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Api\Test\TestCase\Integration\Service\Action;

use Cake\Core\Configure;
use Cake\ORM\TableRegistry;
use CakeDC\Api\Test\ConfigTrait;
use CakeDC\Api\Test\Settings;
use CakeDC\Api\TestSuite\IntegrationTestCase;

/**
 * Real HTTP integration tests for the bulk/collection actions
 * (ArticlesCollectionService: collectionAdd / collectionEdit / collectionDelete).
 */
class CollectionActionsTest extends IntegrationTestCase
{
    use ConfigTrait;

    /**
     * setUp
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        Configure::write('App.fullBaseUrl', 'http://example.com');
        $this->_tokenAccess();
        $this->_loadDefaultExtensions([]);
        $this->getDefaultUser(Settings::USER1);
    }

    /**
     * tearDown
     *
     * @return void
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        Configure::write('Test.Api.Extension');
    }

    public function testCollectionAdd(): void
    {
        $ArticlesTable = TableRegistry::getTableLocator()->get('Articles');
        $before = $ArticlesTable->find()->count();

        $this->sendRequest('/collections/collection/add', 'POST', [
            ['title' => 'Bulk Article 1'],
            ['title' => 'Bulk Article 2'],
        ]);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $this->assertEquals($before + 2, $ArticlesTable->find()->count());
    }

    public function testCollectionEdit(): void
    {
        $ArticlesTable = TableRegistry::getTableLocator()->get('Articles');
        $this->sendRequest('/collections/collection/edit', 'POST', [
            ['id' => 1, 'title' => 'Edited First'],
            ['id' => 2, 'title' => 'Edited Second'],
        ]);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);

        $this->assertSame('Edited First', $ArticlesTable->get(1)->title);
        $this->assertSame('Edited Second', $ArticlesTable->get(2)->title);
    }

    public function testCollectionDelete(): void
    {
        $ArticlesTable = TableRegistry::getTableLocator()->get('Articles');
        $before = $ArticlesTable->find()->count();

        $this->sendRequest('/collections/collection/delete', 'POST', [
            ['id' => 1],
            ['id' => 2],
        ]);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $this->assertEquals($before - 2, $ArticlesTable->find()->count());
        $this->assertFalse($ArticlesTable->exists(['id' => 1]));
        $this->assertFalse($ArticlesTable->exists(['id' => 2]));
    }

    public function testCollectionDeleteValidationError(): void
    {
        $this->sendRequest('/collections/collection/delete', 'POST', [
            ['id' => 1],
            ['title' => 'missing id'],
        ]);
        $result = $this->getJsonResponse();
        $this->assertError($result, 422);
    }
}
