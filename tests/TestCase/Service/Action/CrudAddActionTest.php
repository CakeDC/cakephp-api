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
use CakeDC\Api\Exception\ValidationException;
use CakeDC\Api\Service\Action\CrudAddAction;
use CakeDC\Api\Service\ServiceRegistry;
use CakeDC\Api\Test\ConfigTrait;
use CakeDC\Api\TestSuite\TestCase;

class CrudAddActionTest extends TestCase
{
    use ConfigTrait;

    /**
     * @var CrudAddAction
     */
    public $Action;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        ServiceRegistry::getServiceLocator()->clear();
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
            'title' => 'New message',
        ]);

        $result = $this->Action->execute();
        $this->assertTrue($result instanceof EntityInterface);
        $this->assertNotEmpty($result['id']);
    }

    /**
     * Test load value method
     *
     * @return void
     */
    public function testExecuteValidationError()
    {
        $this->expectException(ValidationException::class);
        $this->_initializeAction([
            'title' => '',
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
        $this->Service = ServiceRegistry::getServiceLocator()->get($this->request->getParam('service'), $options);

        $this->Action = new CrudAddAction([
            'service' => $this->Service,
        ]);
    }
}
