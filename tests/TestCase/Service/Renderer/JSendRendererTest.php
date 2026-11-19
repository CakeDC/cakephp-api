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
use Cake\Http\Response;
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

    public ?FallbackService $Service = null;

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->initializeRequest();
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->Action);
        parent::tearDown();
    }

    /**
     * Test initialize
     *
     * @return void
     */
    public function testRendererInitializeByClassName(): void
    {
        $response = $this->createStub(Response::class);

        $this->initializeRequest([], 'GET', ['response' => $response]);
        $serviceOptions = [
            'version' => null,
            'request' => $this->testRequest,
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
    public function testRendererSuccess(): void
    {
        Configure::write('debug', false);
        $response = $this
            ->getMockBuilder(Response::class)
            ->onlyMethods(['withStatus', 'withType', 'withStringBody'])
            ->getMock();
        $this->initializeRequest([], 'GET', ['response' => $response]);
        $serviceOptions = [
            'version' => null,
            'request' => $this->testRequest,
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
            ->willReturn($response);
        $response->expects($this->once())
            ->method('withStringBody')
            ->with('{"status":"success","data":{"id":1,"name":"alex"}}')
            ->willReturn($response);
        $response->expects($this->once())
            ->method('withType')
            ->with('application/json')
            ->willReturn($response);

        $renderer->response($result);
    }

    /**
     * Test render error
     *
     * @return void
     */
    public function testRendererError(): void
    {
        $response = $this
            ->getMockBuilder(Response::class)
            ->onlyMethods(['withStatus', 'withType', 'withStringBody'])
            ->getMock();

        $this->initializeRequest([], 'GET', ['response' => $response]);
        $serviceOptions = [
            'version' => null,
            'request' => $this->testRequest,
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
            ->willReturn($response);
        $response->expects($this->once())
            ->method('withStatus')
            ->willReturn($response);
        $response->expects($this->once())
            ->method('withType')
            ->with('application/json')
            ->willReturn($response);

        $renderer->error($error);
    }
}
