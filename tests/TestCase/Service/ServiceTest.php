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

namespace CakeDC\Api\Test\App\Service;

use CakeDC\Api\Service\ConfigReader;
use CakeDC\Api\Service\FallbackService;
use CakeDC\Api\Service\Service;
use CakeDC\Api\Service\ServiceRegistry;
use CakeDC\Api\TestSuite\TestCase;
use CakeDC\Api\Test\ConfigTrait;
use CakeDC\Api\Test\FixturesTrait;
use Cake\Controller\Controller;
use Cake\Core\Configure;

class ServiceTest extends TestCase
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
     * Test construct
     *
     * @return void
     * expectedException \CakeDC\Api\Service\Exception\MissingAdapterException
     */
    public function testConstructWithoutAdapter()
    {
        $this->_initializeRequest();
        $this->Service = new FallbackService([
            'service' => 'authors',
            'request' => $this->request,
            'response' => $this->response,
            'baseUrl' => '/authors'
        ]);
    }

    /**
     * Test construct
     *
     * @return void
     */
    public function testConstructWithRendererAsParameter()
    {
        $this->_initializeRequest();
        $this->Service = new FallbackService([
            'service' => 'authors',
            'request' => $this->request,
            'response' => $this->response,
            'baseUrl' => '/authors',
            'rendererClass' => 'CakeDC/Api.Raw'
        ]);
    }

    /**
     * Test load value method
     *
     * @return void
     * @expectedException \Cake\Routing\Exception\MissingRouteException
     */
    public function testActionNotFound()
    {
        $this->_initializeRequest([
            'params' => [
                'service' => 'authors',
            ]
        ], 'DELETE');
        $service = $this->request['service'];
        $options = [
            'version' => null,
            'service' => $service,
            'request' => $this->request,
            'response' => $this->response,
            'baseUrl' => '/authors'
        ];
        $Service = ServiceRegistry::get($service, $options);
        $this->assertTrue($Service instanceof Service);
        $this->assertEquals('authors', $Service->name());

        $this->assertTextEquals('/authors', $Service->baseUrl());
        $action = $Service->buildAction();
    }

    /**
     * Test load value method
     *
     * @return void
     */
    public function testActionInitialize()
    {
        $this->_initializeRequest([
            'params' => [
                'service' => 'authors',
            ]
        ]);
        $service = $this->request['service'];
        $options = [
            'version' => null,
            'service' => $service,
            'request' => $this->request,
            'response' => $this->response,
            'baseUrl' => '/authors'
        ];
        $Service = ServiceRegistry::get($service, $options);
        $this->assertTrue($Service instanceof Service);
        $this->assertEquals('authors', $Service->name());

        $this->assertTextEquals('/authors', $Service->baseUrl());
        $action = $Service->buildAction();
        $this->assertEquals('authors', $action->service()->name());
        $this->assertEquals('authors', $action->table()->table());
    }

    /**
     * Test nested action generation.
     *
     * @return void
     */
    public function testNestedActionInitialize()
    {
        $this->_initializeRequest([
            'params' => [
                'service' => 'authors',
                'pass' => [
                    '1',
                    'articles'
                ]
            ]
        ]);
        $service = $this->request['service'];
        $options = [
            'version' => null,
            'service' => $service,
            'request' => $this->request,
            'response' => $this->response,
            'baseUrl' => '/authors/1/articles',
        ];
        $Service = ServiceRegistry::get($service, $options);
        $this->assertTrue($Service instanceof Service);
        $this->assertTextEquals('/authors/1/articles', $Service->baseUrl());
        $action = $Service->buildAction();
        $this->assertEquals('1', $action->parentId());
        $this->assertEquals('author_id', $action->parentIdName());
        $this->assertEquals('articles', $action->service()->name());
        $this->assertEquals('authors', $action->service()->parent()->name());
        $this->assertEquals('articles', $action->table()->table());
    }

    /**
     * Test nested action generation.
     *
     * @return void
     */
    public function testInitializeActionStoredAsExistsClass()
    {
        $this->_initializeRequest([
            'params' => [
                'service' => 'articles',
                'pass' => [
                    'tag',
                    '1'
                ],
                'post' => [
                    'tag_id' => 1
                ]
            ]
        ], 'PUT');
        $service = $this->request['service'];
        $options = [
            'version' => null,
            'service' => $service,
            'request' => $this->request,
            'response' => $this->response,
            'baseUrl' => '/articles/tag/1',
        ];
        $Service = ServiceRegistry::get($service, $options);
        $this->assertTrue($Service instanceof Service);
        $this->assertTextEquals('/articles/tag/1', $Service->baseUrl());
        $action = $Service->buildAction();
        $this->assertEquals('CakeDC\Api\Test\App\Service\Action\ArticlesTagAction', get_class($action));
    }

    /**
     * Test nested action generation.
     *
     * @return void
     */
    public function testInitializeActionByServiceConfigMap()
    {
        $actionClass = 'CakeDC\Api\Test\App\Service\Action\Author\IndexAction';
        $this->_addSettingByPath('Service.authors.options', [
            'classMap' => [
                'index' => $actionClass
            ]
        ]);
        $config = require(CONFIG . 'api.php');
        Configure::write($config);
        $this->_initializeRequest([
            'params' => [
                'service' => 'authors',
                'pass' => [],
            ]
        ], 'GET');
        $service = $this->request['service'];
        $version = null;
        $options = [
            'version' => $version,
            'service' => $service,
            'request' => $this->request,
            'response' => $this->response,
            'baseUrl' => '/authors'
        ];
        $options += (new ConfigReader())->serviceOptions($service, $version);
        $Service = ServiceRegistry::get($service, $options);
        $this->assertTrue($Service instanceof Service);
        $this->assertTextEquals('/authors', $Service->baseUrl());
        $action = $Service->buildAction();
        $this->assertEquals($actionClass, get_class($action));
        $this->assertTextEquals('custom action applied', $action->process());
    }
}
