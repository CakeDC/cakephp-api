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

use CakeDC\Api\Service\Action\DescribeAction;
use CakeDC\Api\Service\ServiceRegistry;
use CakeDC\Api\TestSuite\TestCase;
use CakeDC\Api\Test\ConfigTrait;
use CakeDC\Api\Test\FixturesTrait;

/**
 * Class DescribeActionTest
 *
 * @package CakeDC\Api\Test\TestCase\Service\Action
 */
class DescribeActionTest extends TestCase
{

    use ConfigTrait;
    use FixturesTrait;

    /**
     * @var DescribeAction
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

        $this->_initializeRequest([
            'params' => [
                'service' => 'describe',
                'pass' => []
            ],
            'query' => [
                'service' => 'articles',
            ]
        ]);
        $service = $this->request->getParam('service');
        $options = [
            'version' => null,
            'service' => $service,
            'request' => $this->request,
            'response' => $this->response,
            'baseUrl' => '/describe',
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
        $this->Action = new DescribeAction([
            'service' => $this->Service,
        ]);

        $result = $this->Action->execute();
        $keys = [
            'entity',
            'schema',
            'validators',
            'relations',
            'actions',
        ];
        $this->assertEquals($keys, array_keys($result));
        $this->assertEquals(['columns', 'labels'], array_keys($result['schema']));
        $this->assertEquals([
            'id' => 'Id',
            'author_id' => 'Author',
            'title' => 'Title',
            'body' => 'Body',
            'published' => 'Published',
        ], $result['schema']['labels']);
    }
}
