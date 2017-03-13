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

use Cake\Datasource\EntityInterface;
use CakeDC\Api\Service\Action\CrudEditAction;
use CakeDC\Api\Service\ServiceRegistry;
use CakeDC\Api\TestSuite\TestCase;
use CakeDC\Api\Test\ConfigTrait;
use CakeDC\Api\Test\FixturesTrait;

class CrudEditActionTest extends TestCase
{

    use ConfigTrait;
    use FixturesTrait;

    /**
     * @var CrudEditAction
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
        unset($this->Service, $this->Action, $this->request);
        parent::tearDown();
    }

    /**
     * Test load value method
     *
     * @return void
     */
    public function testExecuteSuccess()
    {
        $this->_initializeAction(1, [
            'title' => 'New message'
        ]);

        $onFindEntity = false;
        $this->Action->eventManager()->on('Action.Crud.onFindEntity', function () use (&$onFindEntity) {
            $onFindEntity = true;
        });

        $result = $this->Action->execute();
        $this->assertTrue($result instanceof EntityInterface);
        $this->assertTrue($onFindEntity);
    }

    /**
     * Test load value method
     *
     * @return void
     * @expectedException \CakeDC\Api\Exception\ValidationException
     */
    public function testExecuteValidationError()
    {
        $this->_initializeAction(1, [
            'title' => ''
        ]);

        $result = $this->Action->execute();
        $this->assertTrue($result instanceof EntityInterface);
    }

    /**
     * Test load value method
     *
     * @return void
     * @expectedException \Cake\Datasource\Exception\RecordNotFoundException
     */
    public function testExecuteNotFound()
    {
        $this->_initializeAction(999, [
            'title' => 'New message'
        ]);
        $this->Action->execute();
    }

    protected function _initializeAction($id, $post = [])
    {
        $this->_initializeRequest([
            'params' => [
                'service' => 'articles',
                'pass' => [
                    $id,
                ],
            ],
            'post' => $post,
        ], 'PUT');
        $options = [
            'version' => null,
            'service' => $this->request['service'],
            'request' => $this->request,
            'response' => $this->response,
            'baseUrl' => '/articles/' . $id,
        ];
        $this->Service = ServiceRegistry::get($this->request['service'], $options);

        $this->Action = new CrudEditAction([
            'service' => $this->Service,
            'id' => $id,
        ]);
    }
}
