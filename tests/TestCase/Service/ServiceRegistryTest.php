<?php
/**
 * Copyright 2016 - 2018, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2016 - 2018, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Api\Test\TestCase\Service\Action;

use CakeDC\Api\Service\Service;
use CakeDC\Api\Service\ServiceRegistry;
use CakeDC\Api\TestSuite\TestCase;
use CakeDC\Api\Test\ConfigTrait;
use CakeDC\Api\Test\FixturesTrait;
use Cake\Http\Response;
use Cake\Http\ServerRequest;

class ServiceRegistryTest extends TestCase
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
    public function testLoad()
    {
        $this->_initializeRequest([
            'params' => [
                'service' => 'authors',
            ]
        ]);
        $service = $this->request->getParam('service');
        $options = [
            'version' => null,
            'service' => $service,
            'request' => $this->request,
            'response' => $this->response,
            'baseUrl' => '/authors'
        ];
        $Service = ServiceRegistry::get($service, $options);
        $this->assertTrue($Service instanceof Service);
        $this->assertEquals('authors', $Service->getName());
    }

    /**
     * Test load value method
     *
     * @return void
     */
    public function testLoadNested()
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
        $service = 'authors';
        $options = [
            'version' => null,
            'service' => $service,
            'request' => $this->request,
            'response' => $this->response,
            'baseUrl' => '/authors/1/articles',
        ];
        $Service = ServiceRegistry::get($service, $options);
        $this->assertTrue($Service instanceof Service);
        $this->assertTextEquals('/authors/1/articles', $Service->getBaseUrl());
    }
}
