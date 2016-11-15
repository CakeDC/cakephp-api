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

use CakeDC\Api\Service\Action\CrudViewAction;
use CakeDC\Api\Service\ServiceRegistry;
use CakeDC\Api\TestSuite\TestCase;
use CakeDC\Api\Test\ConfigTrait;
use CakeDC\Api\Test\FixturesTrait;

class CrudViewActionTest extends TestCase
{

    use ConfigTrait;
    use FixturesTrait;

    /**
     * @var CrudViewAction
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
                'service' => 'articles',
                'pass' => [
                    '1',
                ]
            ]
        ]);
        $service = $this->request['service'];
        $options = [
            'version' => null,
            'service' => $service,
            'request' => $this->request,
            'response' => $this->response,
            'baseUrl' => '/articles/1'
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
        $this->Action = new CrudViewAction([
            'service' => $this->Service,
            'id' => 1,
        ]);

        $onFindEntity = false;
        $this->Action->eventManager()->on('Action.Crud.onFindEntity', function () use (&$onFindEntity) {
            $onFindEntity = true;
        });

        $result = $this->Action->execute();
        $this->assertTrue($result instanceof \Cake\Datasource\EntityInterface);
        $this->assertTrue($onFindEntity);
    }

    /**
     * Test load value method
     *
     * @return void
     * @expectedException \Cake\Datasource\Exception\RecordNotFoundException
     */
    public function testExecuteNotFound()
    {
        $this->Action = new CrudViewAction([
            'service' => $this->Service,
            'id' => 999,
        ]);

        $result = $this->Action->execute();
        $this->assertTrue($result instanceof \Cake\Datasource\EntityInterface);
    }
}
