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

namespace CakeDC\Api\Test\TestCase\Service\Action;

use CakeDC\Api\Service\Action\CrudAddAction;
use CakeDC\Api\Service\ServiceRegistry;
use CakeDC\Api\Test\ConfigTrait;
use CakeDC\Api\Test\FixturesTrait;
use CakeDC\Api\TestSuite\TestCase;

class ActionTest extends TestCase
{

    use ConfigTrait;
    use FixturesTrait;

    /**
     * @var CrudAddAction
     */
    public $Action;

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
        unset($this->Controller, $this->Service, $this->Action, $this->request);
        parent::tearDown();
    }

    /**
     * Test action calling.
     *
     * @return void
     */
    public function testActionCallOnProcess()
    {
        $this->_initializeController([
            'params' => [
                'service' => 'articles',
                'pass' => [
                    'tag',
                    '1'
                ],
            ],
            'post' => [
                'tag_id' => 1
            ],
        ], 'PUT');
        $service = $this->request['service'];
        $options = [
            'version' => null,
            'controller' => $this->Controller,
        ];
        $Service = ServiceRegistry::get($service, $options);
        $action = $Service->buildAction();
        $result = $action->process();
        $this->assertEquals(true, $result);
    }
}
