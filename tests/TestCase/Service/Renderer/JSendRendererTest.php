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

namespace CakeDC\Api\Test\TestCase\Service\Renderer;

use Cake\Core\Configure;
use CakeDC\Api\Exception\UnauthenticatedException;
use CakeDC\Api\Service\Action\Result;
use CakeDC\Api\Service\FallbackService;
use CakeDC\Api\Service\Renderer\JSendRenderer;
use CakeDC\Api\Test\ConfigTrait;
use CakeDC\Api\TestSuite\TestCase;

class JSendRendererTest extends TestCase
{
    use ConfigTrait;

    public $Action;

    public $request;

    public ?\CakeDC\Api\Service\FallbackService $Service = null;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->_initializeRequest();
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
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
            ->getMockBuilder(\Cake\Http\Response::class)
            ->onlyMethods(['withStatus', 'withType', 'withStringBody'])
            ->getMock();

        $this->_initializeRequest([], 'GET', ['response' => $response]);
        $serviceOptions = [
            'version' => null,
            'request' => $this->request,
            'response' => $response,
            'rendererClass' => 'CakeDC/Api.JSend',
        ];
        $this->Service = new FallbackService($serviceOptions);
        $renderer = $this->Service->getRenderer();
        $this->assertTrue($renderer instanceof JSendRenderer);
    }

    /**
     * Test render response
     *
     * @return void
     */
    public function testRendererSuccess()
    {
        Configure::write('debug', false);
        $response = $this
            ->getMockBuilder(\Cake\Http\Response::class)
            ->onlyMethods(['withStatus', 'withType', 'withStringBody'])
            ->getMock();
        $this->_initializeRequest([], 'GET', ['response' => $response]);
        $serviceOptions = [
            'version' => null,
            'request' => $this->request,
            'response' => $response,
            'rendererClass' => 'CakeDC/Api.JSend',
        ];
        $this->Service = new FallbackService($serviceOptions);

        $result = new Result();
        $statusCode = 200;
        $result->setCode($statusCode);
        $data = ['id' => 1, 'name' => 'alex'];
        $result->setData($data);
        $renderer = $this->Service->getRenderer();

        $response->expects($this->once())
            ->method('withStatus')
            ->with($statusCode)
            ->will($this->returnValue($response));
        $response->expects($this->once())
            ->method('withStringBody')
            ->with('{"status":"success","data":{"id":1,"name":"alex"}}')
            ->will($this->returnValue($response));
        $response->expects($this->once())
            ->method('withType')
            ->with('application/json')
            ->will($this->returnValue($response));

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
            ->getMockBuilder(\Cake\Http\Response::class)
            ->onlyMethods(['withStatus', 'withType', 'withStringBody'])
            ->getMock();

        $this->_initializeRequest([], 'GET', ['response' => $response]);
        $serviceOptions = [
            'version' => null,
            'request' => $this->request,
            'response' => $response,
            'rendererClass' => 'CakeDC/Api.JSend',
        ];
        $this->Service = new FallbackService($serviceOptions);

        Configure::write('debug', false);
        $error = new UnauthenticatedException();
        $renderer = $this->Service->getRenderer();

        $response->expects($this->once())
            ->method('withStringBody')
            ->with('{"status":"error","message":"Unauthenticated","code":401,"data":null}')
            ->will($this->returnValue($response));
        $response->expects($this->once())
            ->method('withStatus')
            ->will($this->returnValue($response));
        $response->expects($this->once())
            ->method('withType')
            ->with('application/json')
            ->will($this->returnValue($response));

        $renderer->error($error);
    }
}
