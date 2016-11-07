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
use CakeDC\Api\Service\Renderer\RawRenderer;
use CakeDC\Api\Service\Service;
use CakeDC\Api\Test\ConfigTrait;
use CakeDC\Api\Test\FixturesTrait;
use Cake\Core\Configure;
use CakeDC\Api\TestSuite\TestCase;

class RawRendererTest extends TestCase
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
            'rendererClass' => 'CakeDC/Api.Raw'
        ];
        $this->Service = new FallbackService($serviceOptions);
        $renderer = $this->Service->renderer();
        $this->assertTrue($renderer instanceof RawRenderer);
    }

    /**
     * Test render response
     *
     * @return void
     */
    public function testRendererSuccess()
    {
        $response = $this
            ->getMockBuilder('Cake\Network\Response')
            ->setMethods(['statusCode', 'type', 'body'])
            ->getMock();

        $this->_initializeController([], 'GET', ['response' => $response]);
        $serviceOptions = [
            'version' => null,
            'controller' => $this->Controller,
            'rendererClass' => 'CakeDC/Api.Raw'
        ];
        $this->Service = new FallbackService($serviceOptions);

        $result = new Result();
        $statusCode = 200;
        $result->code($statusCode);
        $data = 'Updated!';
        $result->data($data);
        $renderer = $this->Service->renderer();

        $response->expects($this->once())
                 ->method('statusCode')
                 ->with($statusCode);
        $response->expects($this->once())
                 ->method('body')
                ->with($data);
        $response->expects($this->once())
                 ->method('type')
                 ->with('text/plain');

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
            'rendererClass' => 'CakeDC/Api.Raw'
        ];
        $this->Service = new FallbackService($serviceOptions);

        Configure::write('debug', 0);
        $error = new UnauthorizedException();
        $renderer = $this->Service->renderer();

        $response->expects($this->once())
            ->method('body')
            ->with('Unauthorized');
        $response->expects($this->once())
            ->method('type')
            ->with('text/plain');

        $renderer->error($error);
    }
}
