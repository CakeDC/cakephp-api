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

namespace CakeDC\Api\Test\TestCase\Service;

use CakeDC\Api\Service\ListingService;
use CakeDC\Api\Service\ServiceRegistry;
use CakeDC\Api\TestSuite\TestCase;
use CakeDC\Api\Test\ConfigTrait;
use CakeDC\Api\Test\FixturesTrait;

/**
 * Class ListingServiceTest
 *
 * @package CakeDC\Api\Test\TestCase\Service
 */
class ListingServiceTest extends TestCase
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
        $this->_initializeRequest([
            'params' => [
                'service' => 'listing',
            ]
        ]);
        $service = $this->request['service'];
        $options = [
            'version' => null,
            'service' => $service,
            'request' => $this->request,
            'response' => $this->response,
            'baseUrl' => '/listing'
        ];
        $Service = ServiceRegistry::get($service, $options);
        $this->assertTrue($Service instanceof ListingService);
        $this->assertEquals('listing', $Service->name());

        $action = $Service->buildAction();
        $result = $action->process();
        $expected = [
            'articles',
            'articles_tags',
            'authors',
            'tags',
        ];
        sort($expected);
        sort($result);
        $this->assertEquals($expected, $result);
    }
}
