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

namespace CakeDC\Api\Test\TestCase\Service\Renderer;

use CakeDC\Api\Exception\UnauthenticatedException;
use CakeDC\Api\Service\Action\Result;
use CakeDC\Api\Service\FallbackService;
use CakeDC\Api\Service\Renderer\XmlRenderer;
use CakeDC\Api\Service\Service;
use CakeDC\Api\TestSuite\TestCase;
use CakeDC\Api\Test\ConfigTrait;
use CakeDC\Api\Test\FixturesTrait;
use Cake\Core\Configure;

class XmlRendererTest extends TestCase
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

        $this->_initializeRequest();
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
            ->setMethods(['withStatus', 'withType', 'withStringBody'])
            ->getMock();

        $this->_initializeRequest([], 'GET', ['response' => $response]);
        $serviceOptions = [
            'version' => null,
            'request' => $this->request,
            'response' => $response,
            'rendererClass' => 'CakeDC/Api.Xml'
        ];
        $this->Service = new FallbackService($serviceOptions);
        $renderer = $this->Service->getRenderer();
        $this->assertTrue($renderer instanceof XmlRenderer);
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
            ->setMethods(['withStatus', 'withType', 'withStringBody'])
            ->getMock();

        $this->_initializeRequest([], 'GET', ['response' => $response]);
        $serviceOptions = [
            'version' => null,
            'request' => $this->request,
            'response' => $response,
            'rendererClass' => 'CakeDC/Api.Xml'
        ];
        $this->Service = new FallbackService($serviceOptions);

        $result = new Result();
        $statusCode = 200;
        $result->code($statusCode);
        $data = 'Updated!';
        $result->data($data);
        $renderer = $this->Service->getRenderer();

        $response->expects($this->once())
                 ->method('withStatus')
                 ->with($statusCode)
                 ->will($this->returnValue($response));
        $response->expects($this->once())
                ->method('withStringBody')
                ->with($this->_xmlMessage('<data><value>Updated!</value></data>'))
                ->will($this->returnValue($response));
        $response->expects($this->once())
                 ->method('withType')
                 ->with('application/xml')
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
            ->getMockBuilder('Cake\Network\Response')
            ->setMethods(['withStatus', 'withType', 'withStringBody'])
            ->getMock();

        $this->_initializeRequest([], 'GET', ['response' => $response]);
        $serviceOptions = [
            'version' => null,
            'request' => $this->request,
            'response' => $response,
            'rendererClass' => 'CakeDC/Api.Xml'
        ];
        $this->Service = new FallbackService($serviceOptions);

        Configure::write('debug', 0);
        $error = new UnauthenticatedException();
        $renderer = $this->Service->getRenderer();

        $response->expects($this->once())
                ->method('withStringBody')
                ->with($this->_xmlMessage('<error><code>401</code><message>Unauthenticated</message></error>'))
                ->will($this->returnValue($response));
        $response->expects($this->once())
                 ->method('withType')
                 ->with('application/xml')
                ->will($this->returnValue($response));

        $renderer->error($error);
    }

    protected function _xmlMessage($text)
    {
        return '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . $text . "\n";
    }
}
