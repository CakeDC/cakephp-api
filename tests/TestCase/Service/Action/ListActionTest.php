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

use CakeDC\Api\Service\Action\ListAction;
use CakeDC\Api\Service\ServiceRegistry;
use CakeDC\Api\Test\ConfigTrait;
use CakeDC\Api\Test\FixturesTrait;
use CakeDC\Api\TestSuite\TestCase;

/**
 * Class ListActionTest
 *
 * @package CakeDC\Api\Test\TestCase\Service\Action
 */
class ListActionTest extends TestCase
{

    use ConfigTrait;
    use FixturesTrait;

    /**
     * @var ListAction
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

        $this->_initializeController([
            'params' => [
                'service' => 'listing',
                'pass' => []
            ]
        ]);
        $service = $this->request['service'];
        $options = [
            'version' => null,
            'controller' => $this->Controller,
        ];
        $this->Service = ServiceRegistry::get($service, $options);
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
