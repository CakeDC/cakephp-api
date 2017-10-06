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

namespace CakeDC\Api\Test\TestCase\Service\Action;

use CakeDC\Api\Service\Action\CrudIndexAction;
use CakeDC\Api\Service\Action\ExtensionRegistry;
use CakeDC\Api\Service\Action\Extension\PaginateExtension;
use CakeDC\Api\Service\Action\Extension\SortExtension;
use CakeDC\Api\Service\FallbackService;
use CakeDC\Api\TestSuite\TestCase;
use CakeDC\Api\Test\ConfigTrait;
use CakeDC\Api\Test\FixturesTrait;
use Cake\Network\Request;
use Cake\Network\Response;

class ExtensionRegistryTest extends TestCase
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

        $request = new Request();
        $response = new Response();
        $service = new FallbackService([
            'request' => $request,
            'response' => $response
        ]);
        $this->Action = new CrudIndexAction([
            'service' => $service,
            'request' => $request,
            'response' => $response
        ]);

        $this->ExtensionRegistry = new ExtensionRegistry($this->Action);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->ExtensionRegistry, $this->Action);
        parent::tearDown();
    }

    /**
     * Test load value method
     *
     * @return void
     */
    public function testLoad()
    {
        $extension = $this->ExtensionRegistry->load('CakeDC/Api.Sort', []);
        $this->assertTrue($extension instanceof SortExtension);
        $extension = $this->ExtensionRegistry->load('CakeDC/Api.Paginate', []);
        $this->assertTrue($extension instanceof PaginateExtension);
    }

    /**
     * Test load unexists class  method
     *
     * @expectedException \CakeDC\Api\Service\Exception\MissingExtensionException
     * @return void
     */
    public function testLoadWrongClass()
    {
        $this->ExtensionRegistry->load('Unknown', []);
    }
}
