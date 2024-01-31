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

use Cake\Datasource\EntityInterface;
use Cake\Datasource\Exception\RecordNotFoundException;
use CakeDC\Api\Service\Action\CrudViewAction;
use CakeDC\Api\Service\ServiceRegistry;
use CakeDC\Api\Test\ConfigTrait;
use CakeDC\Api\TestSuite\TestCase;

class CrudViewActionTest extends TestCase
{
    use ConfigTrait;

    public $request;

    public $response;

    /**
     * @var \CakeDC\Api\Service\Service|mixed
     */
    public $Service;

    public ?\CakeDC\Api\Service\Action\CrudViewAction $Action = null;

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
                'service' => 'articles',
                'pass' => [
                    '1',
                ],
            ],
        ]);
        $service = $this->request->getParam('service');
        $options = [
            'version' => null,
            'service' => $service,
            'request' => $this->request,
            'response' => $this->response,
            'baseUrl' => '/articles/1',
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
        $this->Action = new CrudViewAction([
            'service' => $this->Service,
            'id' => 1,
        ]);

        $onFindEntity = $afterFindEntity = false;
        $this->Action->getEventManager()->on('Action.Crud.onFindEntity', function () use (&$onFindEntity) {
            $onFindEntity = true;
        });
        $this->Action->getEventManager()->on('Action.Crud.afterFindEntity', function () use (&$afterFindEntity) {
            $afterFindEntity = true;
        });

        $result = $this->Action->execute();
        $this->assertTrue($result instanceof EntityInterface);
        $this->assertTrue($onFindEntity);
        $this->assertTrue($afterFindEntity);
    }

    /**
     * Test load value method
     *
     * @return void
     */
    public function testExecuteNotFound()
    {
        $this->expectException(RecordNotFoundException::class);
        $this->Action = new CrudViewAction([
            'service' => $this->Service,
            'id' => 999,
        ]);

        $result = $this->Action->execute();
        $this->assertTrue($result instanceof EntityInterface);
    }
}
