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

namespace CakeDC\Api\Test\TestCase\Service;

use CakeDC\Api\Service\CollectionService;
use CakeDC\Api\Service\ServiceRegistry;
use CakeDC\Api\TestSuite\TestCase;
use CakeDC\Api\Test\ConfigTrait;
use CakeDC\Api\Test\FixturesTrait;
use Cake\ORM\TableRegistry;

/**
 * Class CollectionServiceTest
 *
 * @package CakeDC\Api\Test\TestCase\Service
 */
class CollectionServiceTest extends TestCase
{
    use ConfigTrait;
    use FixturesTrait;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        ServiceRegistry::clear();
        parent::tearDown();
    }

    /**
     * Test load value method
     *
     * @return void
     */
    public function testActionProcess()
    {
        $ArticlesTable = TableRegistry::get('Articles');
        $initialCount = $ArticlesTable->find()->count();
        $this->_initializeRequest([
            'params' => [
                'service' => 'articlesCollection',
            ],
            'post' => [
                ['title' => 'title1'],
                ['title' => 'title2'],
                ['title' => 'title3'],
            ]
        ], 'POST');
        $service = $this->request['service'];
        $options = [
            'version' => null,
            'service' => $service,
            'request' => $this->request,
            'response' => $this->response,
            'baseUrl' => '/articles_collection/collection/add'
        ];
        $Service = ServiceRegistry::get($service, $options);
        $this->assertTrue($Service instanceof CollectionService);
        $this->assertEquals('articles_collection', $Service->getName());

        $action = $Service->buildAction();
        $action->Auth->allow('*');
        $action->setTable($ArticlesTable);
        $result = $action->process();
        $finalCount = $ArticlesTable->find()->count();
        $this->assertEquals(3, $finalCount - $initialCount, 'We should have added 3 new articles');
    }

    /**
     * Test load routes
     *
     * @return void
     */
    public function testLoadRoutes()
    {
        $ArticlesTable = TableRegistry::get('Articles');
        $initialCount = $ArticlesTable->find()->count();
        $this->_initializeRequest([
            'params' => [
                'service' => 'articlesCollection',
            ],
            'post' => [
                ['title' => 'title1'],
                ['title' => 'title2'],
                ['title' => 'title3'],
            ]
        ], 'POST');
        $service = $this->request['service'];
        $options = [
            'version' => null,
            'service' => $service,
            'request' => $this->request,
            'response' => $this->response,
            'baseUrl' => '/articles_collection/collection/add'
        ];
        $Service = ServiceRegistry::get($service, $options);
        $this->assertTrue($Service instanceof CollectionService);
        $this->assertEquals('articles_collection', $Service->getName());
        $routeTemplates = collection($Service->routes())->extract(function ($route) {
            return $route->template;
        })->toArray();
        $this->assertSame([
            '/articles_collection/collection/add',
            '/articles_collection/collection/edit',
            '/articles_collection/collection/delete'
        ], $routeTemplates);
    }
}
