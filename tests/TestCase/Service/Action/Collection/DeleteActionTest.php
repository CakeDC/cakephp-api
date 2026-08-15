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

namespace CakeDC\Api\Test\TestCase\Service\Action\Collection;

use Cake\ORM\TableRegistry;
use CakeDC\Api\Exception\ValidationException;
use CakeDC\Api\Service\Action\Collection\DeleteAction;
use CakeDC\Api\Service\ServiceRegistry;
use CakeDC\Api\Test\ConfigTrait;
use CakeDC\Api\TestSuite\TestCase;

class DeleteActionTest extends TestCase
{
    use ConfigTrait;

    /**
     * @var mixed|\CakeDC\Api\Service\Service
     */
    public $Service;

    public $request;

    public $response;

    public ?\CakeDC\Api\Service\Action\Collection\DeleteAction $Action = null;

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
        unset($this->Service, $this->Action, $this->request);
        parent::tearDown();
    }

    /**
     * @return void
     */
    public function testExecuteSuccess(): void
    {
        $ArticlesTable = TableRegistry::getTableLocator()->get('Articles');
        $initialCount = $ArticlesTable->find()->count();
        $this->_initializeAction([
            ['id' => 1],
            ['id' => 2],
        ]);

        $this->Action->execute();
        $finalCount = $ArticlesTable->find()->count();
        $this->assertEquals(-2, $finalCount - $initialCount, 'We should have added 2 new articles');
    }

    /**
     * @return void
     */
    public function testValidationPostNotArray(): void
    {
        $this->expectException(ValidationException::class);
        $this->_initializeAction(
            ['id' => 1]
        );
        $this->Action->execute();
    }

    /**
     * @return void
     */
    public function testValidationPostEmpty(): void
    {
        $this->expectException(ValidationException::class);
        $this->_initializeAction();
        $this->Action->execute();
    }

    /**
     * @return void
     */
    public function testValidationPostString(): void
    {
        $this->expectException(ValidationException::class);
        $this->_initializeAction(['something' => 'value']);
        $this->Action->execute();
    }

    /**
     * @return void
     */
    public function testExecuteValidationEntityNotValid(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Validation failed');
        $this->_initializeAction([
            ['not-id' => 'something'],
            ['blank' => new \ArrayObject()],
        ]);

        $this->Action->execute();
    }

    /**
     * @return void
     */
    public function testValidatesEntity(): void
    {
        $this->_initializeAction([
            ['id' => 1],
            ['id' => 7],
        ]);

        $this->assertTrue($this->Action->validates());
    }

    /**
     * @return void
     */
    public function testValidatesEntityNotValid(): void
    {
        $this->_initializeAction([
            ['id' => 1],
            ['id' => ''],
        ]);

        try {
            $this->Action->validates();
            $this->fail('ValidationException was expected');
        } catch (ValidationException $validationException) {
            $this->assertSame([
                // note the index here is important, first entity (0) is valid
                1 => [
                    'id' => [
                        '_empty' => 'Missing id',
                    ],
                ],
            ], $validationException->getValidationErrors());
        }
    }

    protected function _initializeAction($post = []): void
    {
        $this->_initializeRequest([
            'params' => [
                'service' => 'articlesCollection',
            ],
            'post' => $post,
        ], 'POST');
        $options = [
            'version' => null,
            'service' => null,
            'request' => $this->request,
            'response' => $this->response,
            'baseUrl' => '/articles_collection/collection/delete',
        ];
        $this->Service = ServiceRegistry::getServiceLocator()->get($this->request->getParam('service'), $options);

        $this->Action = new DeleteAction([
            'service' => $this->Service,
        ]);
        $this->Action->setTable(TableRegistry::getTableLocator()->get('Articles'));
    }
}
