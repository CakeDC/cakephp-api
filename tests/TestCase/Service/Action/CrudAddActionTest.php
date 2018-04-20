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

use CakeDC\Api\Service\Action\CrudAddAction;
use CakeDC\Api\Service\ServiceRegistry;
use CakeDC\Api\TestSuite\TestCase;
use CakeDC\Api\Test\ConfigTrait;
use CakeDC\Api\Test\FixturesTrait;

use Cake\Datasource\EntityInterface;

class CrudAddActionTest extends TestCase
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
        unset($this->Action, $this->request);
        parent::tearDown();
    }

    /**
     * Test load value method
     *
     * @return void
     */
    public function testExecuteSuccess()
    {
        $this->_initializeAction([
            'title' => 'New message'
        ]);

        $result = $this->Action->execute();
        $this->assertTrue($result instanceof EntityInterface);
        $this->assertNotEmpty($result['id']);
    }

    /**
     * Test load value method
     *
     * @return void
     * @expectedException \CakeDC\Api\Exception\ValidationException
     */
    public function testExecuteValidationError()
    {
        $this->_initializeAction([
            'title' => ''
        ]);

        $result = $this->Action->execute();
        $this->assertTrue($result instanceof EntityInterface);
    }

    protected function _initializeAction($post = [])
    {
        $this->_initializeRequest([
            'params' => [
                'service' => 'articles',
                'pass' => [],
            ],
            'post' => $post,
        ], 'POST');
        $options = [
            'version' => null,
            'service' => $this->request->getParam('service'),
            'request' => $this->request,
            'response' => $this->response,
            'baseUrl' => '/articles',
        ];
        $this->Service = ServiceRegistry::get($this->request->getParam('service'), $options);

        $this->Action = new CrudAddAction([
            'service' => $this->Service,
        ]);
    }
}
