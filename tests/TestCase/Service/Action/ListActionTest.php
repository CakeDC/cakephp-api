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

use CakeDC\Api\Service\Action\ListAction;
use CakeDC\Api\Service\ServiceRegistry;
use CakeDC\Api\Test\ConfigTrait;
use CakeDC\Api\TestSuite\TestCase;

/**
 * Class ListActionTest
 *
 * @package CakeDC\Api\Test\TestCase\Service\Action
 */
class ListActionTest extends TestCase
{
    use ConfigTrait;

    public $request;

    public $response;

    /**
     * @var \CakeDC\Api\Service\Service|mixed
     */
    public $Service;

    public ?\CakeDC\Api\Service\Action\ListAction $Action = null;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->_initializeRequest([
            'params' => [
                'service' => 'listing',
                'pass' => [],
            ],
        ]);
        $service = $this->request->getParam('service');
        $options = [
            'version' => null,
            'service' => $service,
            'request' => $this->request,
            'response' => $this->response,
            'baseUrl' => '/listing',
        ];
        $this->Service = ServiceRegistry::getServiceLocator()->get($service, $options);
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
     * Test load value method
     *
     * @return void
     */
    public function testExecuteSuccess()
    {
        $this->Action = new ListAction([
            'service' => $this->Service,
        ]);

        $result = $this->Action->execute();
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
