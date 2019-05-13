<?php
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

use CakeDC\Api\Exception\UnauthenticatedException;
use CakeDC\Api\Service\Action\Result;
use CakeDC\Api\Service\FallbackService;
use CakeDC\Api\Service\Renderer\FlysystemRenderer;
use CakeDC\Api\Service\Service;
use CakeDC\Api\TestSuite\TestCase;
use CakeDC\Api\Test\ConfigTrait;
use CakeDC\Api\Test\FixturesTrait;
use Cake\Core\Configure;
use League\Flysystem\Filesystem;
use League\Flysystem\Vfs\VfsAdapter;
use VirtualFileSystem\FileSystem as Vfs;

class FlysystemRendererTest extends TestCase
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
            ->getMockBuilder('Cake\Http\Response')
            ->setMethods(['withStatus', 'withType', 'withStringBody'])
            ->getMock();

        $this->_initializeRequest([], 'GET', ['response' => $response]);
        $serviceOptions = [
            'version' => null,
            'request' => $this->request,
            'response' => $response,
            'rendererClass' => 'CakeDC/Api.Flysystem'
        ];
        $this->Service = new FallbackService($serviceOptions);
        $renderer = $this->Service->getRenderer();
        $this->assertTrue($renderer instanceof FlysystemRenderer);
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
            ->getMockBuilder('Cake\Http\Response')
            ->setMethods(['withStatus', 'withType', 'withStringBody'])
            ->getMock();

        $this->_initializeRequest([], 'GET', ['response' => $response]);
        $serviceOptions = [
            'version' => null,
            'request' => $this->request,
            'response' => $response,
            'rendererClass' => 'CakeDC/Api.Flysystem'
        ];
        $this->Service = new FallbackService($serviceOptions);

        $result = new Result();
        $statusCode = 200;
        $result->setCode($statusCode);

        $vfs = new Vfs();
        $path = "/my-file.zip";
        $vfs->createFile($path, 'content-for-download');

        $filesystem = new Filesystem(new VfsAdapter($vfs));
        $data = compact('filesystem', 'path');
        $result->setData($data);
        $renderer = $this->Service->getRenderer();

        $renderer->response($result);

        $headers = $this->Service->getResponse()->getHeaders();
        $this->assertEquals(
            ['file'],
            $headers['Content-Type']
        );
        $this->assertArrayHasKey('Cache-Control', $headers);
        $this->assertArrayHasKey('Date', $headers);
        $this->assertArrayHasKey('Last-Modified', $headers);
        $this->assertArrayHasKey('Expires', $headers);
    }

    /**
     * Test render response
     *
     * @return void
     */
    public function testRendereFileNotFound()
    {
        Configure::write('debug', 0);
        $response = $this
            ->getMockBuilder('Cake\Http\Response')
            ->setMethods(['withStatus', 'withType', 'withStringBody'])
            ->getMock();

        $this->_initializeRequest([], 'GET', ['response' => $response]);
        $serviceOptions = [
            'version' => null,
            'request' => $this->request,
            'response' => $response,
            'rendererClass' => 'CakeDC/Api.Flysystem'
        ];
        $this->Service = new FallbackService($serviceOptions);

        $result = new Result();
        $statusCode = 200;
        $result->setCode($statusCode);

        $vfs = new Vfs();
        $path = "/my-file-not-found.zip";

        $filesystem = new Filesystem(new VfsAdapter($vfs));
        $data = compact('filesystem', 'path');
        $result->setData($data);
        $renderer = $this->Service->getRenderer();
        $response->expects($this->once())
            ->method('withStatus')
            ->with(404)
            ->will($this->returnValue($response));
        $response->expects($this->never())
            ->method('withType');
        $response->expects($this->never())
            ->method('withStringBody');

        $renderer->response($result);

        $headers = $this->Service->getResponse()->getHeaders();
        $this->assertEquals(
            ['text/html; charset=UTF-8'],
            $headers['Content-Type']
        );
        $this->assertEmpty((string)$this->Service->getResponse()->getBody());
    }

    /**
     * Test render error
     *
     * @return void
     */
    public function testRendererError()
    {
        $response = $this
            ->getMockBuilder('Cake\Http\Response')
            ->setMethods(['withStatus', 'withType', 'withStringBody'])
            ->getMock();

        $this->_initializeRequest([], 'GET', ['response' => $response]);
        $serviceOptions = [
            'version' => null,
            'request' => $this->request,
            'response' => $response,
            'rendererClass' => 'CakeDC/Api.Flysystem'
        ];
        $this->Service = new FallbackService($serviceOptions);

        Configure::write('debug', 0);
        $error = new UnauthenticatedException();
        $renderer = $this->Service->getRenderer();

        $response->expects($this->once())
            ->method('withStatus')
            ->with(401)
            ->will($this->returnValue($response));
        $response->expects($this->never())
            ->method('withType');
        $response->expects($this->never())
            ->method('withStringBody');

        $renderer->error($error);
    }

    /**
     * Test render error
     *
     * @return void
     */
    public function testRendererErrorEmptyExceptionCode()
    {
        $response = $this
            ->getMockBuilder('Cake\Http\Response')
            ->setMethods(['withStatus', 'withType', 'withStringBody'])
            ->getMock();

        $this->_initializeRequest([], 'GET', ['response' => $response]);
        $serviceOptions = [
            'version' => null,
            'request' => $this->request,
            'response' => $response,
            'rendererClass' => 'CakeDC/Api.Flysystem'
        ];
        $this->Service = new FallbackService($serviceOptions);

        Configure::write('debug', 0);
        $error = new \Exception();
        $renderer = $this->Service->getRenderer();

        $response->expects($this->once())
            ->method('withStatus')
            ->with(500)
            ->will($this->returnValue($response));
        $response->expects($this->never())
            ->method('withType');
        $response->expects($this->never())
            ->method('withStringBody');

        $renderer->error($error);
    }
}
