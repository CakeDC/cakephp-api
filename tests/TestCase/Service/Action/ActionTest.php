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

use CakeDC\Api\Service\ServiceRegistry;
use CakeDC\Api\Test\ConfigTrait;
use CakeDC\Api\TestSuite\TestCase;

class ActionTest extends TestCase
{
    use ConfigTrait;

    public $Service;

    /**
     * @var AddAction
     */
    public $Action;

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        ServiceRegistry::getServiceLocator()->clear();
        unset($this->Service, $this->Action, $this->testRequest);
        parent::tearDown();
    }

    /**
     * Test action calling.
     *
     * @return void
     */
    public function testActionCallOnProcess(): void
    {
        $this->initializeRequest([
            'params' => [
                'service' => 'articles',
                'pass' => [
                    'tag',
                    '1',
                ],
            ],
            'post' => [
                'tag_id' => 1,
            ],
        ], 'PUT');
        $service = $this->testRequest->getParam('service');
        $options = [
            'version' => null,
            'service' => $service,
            'request' => $this->testRequest,
            'response' => $this->testResponse,
            'baseUrl' => '/articles/tag/1',
        ];
        $Service = ServiceRegistry::getServiceLocator()->get($service, $options);
        $action = $Service->buildAction();
        $result = $action->process();
        $this->assertEquals(true, $result);
    }
}
