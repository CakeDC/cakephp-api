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

namespace CakeDC\Api\Test\TestCase\Service\Action;

use Cake\Http\Response;
use Cake\Http\ServerRequest;
use CakeDC\Api\Service\Action\CrudIndexAction;
use CakeDC\Api\Service\Action\Extension\PaginateExtension;
use CakeDC\Api\Service\Action\Extension\SortExtension;
use CakeDC\Api\Service\Action\ExtensionRegistry;
use CakeDC\Api\Service\Exception\MissingExtensionException;
use CakeDC\Api\Service\FallbackService;
use CakeDC\Api\Test\ConfigTrait;
use CakeDC\Api\TestSuite\TestCase;

class ExtensionRegistryTest extends TestCase
{
    use ConfigTrait;

    /**
     * @var \CakeDC\Api\Service\Action\CrudIndexAction|mixed
     */
    public $Action;

    /**
     * @var \CakeDC\Api\Service\Action\ExtensionRegistry|mixed
     */
    public $ExtensionRegistry;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $request = new ServerRequest();
        $response = new Response();
        $service = new FallbackService([
            'request' => $request,
            'response' => $response,
        ]);
        $this->Action = new CrudIndexAction([
            'service' => $service,
            'request' => $request,
            'response' => $response,
        ]);

        $this->ExtensionRegistry = new ExtensionRegistry($this->Action);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
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
     * @return void
     */
    public function testLoadWrongClass()
    {
        $this->expectException(MissingExtensionException::class);
        $this->ExtensionRegistry->load('Unknown', []);
    }
}
