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

namespace CakeDC\Api\Test\TestCase\Service\Renderer;

use CakeDC\Api\Exception\UnauthorizedException;
use CakeDC\Api\Service\Action\Result;
use CakeDC\Api\Service\FallbackService;
use CakeDC\Api\Service\Renderer\JsonRenderer;
use CakeDC\Api\Service\Service;
use CakeDC\Api\Test\ConfigTrait;
use CakeDC\Api\Test\FixturesTrait;
use Cake\Core\Configure;
use CakeDC\Api\TestSuite\TestCase;

class JsonRendererTest extends TestCase
{

    use ConfigTrait;
    use FixturesTrait;

    /**
     * @var Service
     */
    public $Service;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->_initializeController();
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Action);
        parent::tearDown();
    }

    /**
     * Test initialize
     *
     * @return void
     */
    public function testRendererInitializeByClassName()
    {
        $response = $this
            ->getMockBuilder('Cake\Network\Response')
            ->setMethods(['statusCode', 'type', 'body'])
            ->getMock();

        $this->_initializeController([], 'GET', ['response' => $response]);
        $serviceOptions = [
            'version' => null,
            'controller' => $this->Controller,
            'rendererClass' => 'CakeDC/Api.Json'
        ];
        $this->Service = new FallbackService($serviceOptions);
        $renderer = $this->Service->renderer();
        $this->assertTrue($renderer instanceof JsonRenderer);
    }

    /**
     * Test render response
     *
     * @return void
     */
    public function testRendererSuccess()
    {
        Configure::write('debug', 0);
        $response = $this
            ->getMockBuilder('Cake\Network\Response')
            ->setMethods(['statusCode', 'type', 'body'])
            ->getMock();

        $this->_initializeController([], 'GET', ['response' => $response]);
        $serviceOptions = [
            'version' => null,
            'controller' => $this->Controller,
            'rendererClass' => 'CakeDC/Api.Json'
        ];
        $this->Service = new FallbackService($serviceOptions);

        $result = new Result();
        $statusCode = 200;
        $result->code($statusCode);
        $data = ['id' => 1, 'name' => 'alex'];
        $result->data($data);
        $renderer = $this->Service->renderer();

        $response->expects($this->once())
                 ->method('statusCode')
                 ->with($statusCode);
        $response->expects($this->once())
                 ->method('body')
                ->with('{"id":1,"name":"alex"}');
        $response->expects($this->once())
                 ->method('type')
                 ->with('application/json');

        $renderer->response($result);
    }

    /**
     * Test render error
     *
     * @return void
     */
    public function testRendererError()
    {
        $response = $this
            ->getMockBuilder('Cake\Network\Response')
            ->setMethods(['statusCode', 'type', 'body'])
            ->getMock();

        $this->_initializeController([], 'GET', ['response' => $response]);
        $serviceOptions = [
            'version' => null,
            'controller' => $this->Controller,
            'rendererClass' => 'CakeDC/Api.Json'
        ];
        $this->Service = new FallbackService($serviceOptions);

        Configure::write('debug', 0);
        $error = new UnauthorizedException();
        $renderer = $this->Service->renderer();

        $response->expects($this->once())
            ->method('body')
            ->with('{"error":{"code":401,"message":"Unauthorized"}}');
        $response->expects($this->once())
            ->method('type')
            ->with('application/json');

        $renderer->error($error);
    }
}
