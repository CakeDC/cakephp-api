<?php
/**
 * Copyright 2016 - 2017, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2016 - 2017, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Api\Test\TestCase\Service\Action\Collection;

use CakeDC\Api\Exception\ValidationException;
use CakeDC\Api\Service\Action\Collection\AddEditAction;
use CakeDC\Api\Service\FallbackService;
use CakeDC\Api\Service\ServiceRegistry;
use CakeDC\Api\TestSuite\TestCase;
use CakeDC\Api\Test\ConfigTrait;
use CakeDC\Api\Test\FixturesTrait;
use Cake\ORM\TableRegistry;

class AddEditActionTest extends TestCase
{
    use ConfigTrait;
    use FixturesTrait;

    /**
     * @var AddEditAction
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
     * @return void
     */
    public function testExecuteSuccess()
    {
        $ArticlesTable = TableRegistry::get('Articles');
        $initialCount = $ArticlesTable->find()->count();
        $this->_initializeAction([
            ['title' => 'Article1'],
            ['title' => 'Article2']
        ]);

        $this->Action->execute();
        $finalCount = $ArticlesTable->find()->count();
        $this->assertEquals(2, $finalCount - $initialCount, 'We should have added 2 new articles');
    }

    /**
     * @return void
     * @expectedException \CakeDC\Api\Exception\ValidationException
     */
    public function testValidationPostNotArray()
    {
        $this->_initializeAction(
            ['title' => 'Article1']
        );
        $this->Action->execute();
    }

    /**
     * @return void
     * @expectedException \CakeDC\Api\Exception\ValidationException
     */
    public function testValidationPostEmpty()
    {
        $this->_initializeAction();
        $this->Action->execute();
    }

    /**
     * @return void
     * @expectedException \CakeDC\Api\Exception\ValidationException
     */
    public function testValidationPostString()
    {
        $this->_initializeAction('something');
        $this->Action->execute();
    }

    /**
     * @return void
     * @expectedException \CakeDC\Api\Exception\ValidationException
     * @expectedExceptionMessage Validation on Articles failed
     */
    public function testExecuteValidationEntityNotValid()
    {
        $ArticlesTable = TableRegistry::get('Articles');
        $initialCount = $ArticlesTable->find()->count();
        $this->_initializeAction([
            ['title' => 'Article1'],
            ['title' => '']
        ]);

        $this->Action->execute();
        $finalCount = $ArticlesTable->find()->count();
        $this->assertEquals(2, $finalCount - $initialCount, 'We should have added 2 new articles');
    }

    /**
     * @return void
     */
    public function testValidatesEntity()
    {
        $this->_initializeAction([
            ['title' => 'Article1'],
            ['title' => 'Article2']
        ]);

        $this->assertTrue($this->Action->validates());
    }

    /**
     * @return void
     */
    public function testValidatesEntityNotValid()
    {
        $this->_initializeAction([
            ['title' => 'Article1'],
            ['title' => '']
        ]);

        try {
            $this->Action->validates();
            $this->fail('ValidationException was expected');
        } catch (ValidationException $ex) {
            $this->assertSame([
                // note the index here is important, first entity (0) is valid
                1 => [
                    'title' => [
                        '_empty' => 'This field cannot be left empty'
                    ]
                ]
            ], $ex->getValidationErrors());
        }
    }

    public function testIntegrationArticlesCollection()
    {
        $ArticlesTable = TableRegistry::get('Articles');
        $initialCount = $ArticlesTable->find()->count();
        $post = [
            ['title' => 'Article1'],
            ['title' => 'Article2']
        ];

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
            'baseUrl' => '/articles_collection/collection/add'
        ];
        $Service = ServiceRegistry::get($this->request['service'], $options);
        $this->assertTrue($Service instanceof FallbackService);
        $this->assertEquals('articles_collection', $Service->getName());

        $action = $Service->buildAction();
        $action->Auth->allow('*');

        $action->setTable($ArticlesTable);
        $result = $action->process();
        $finalCount = $ArticlesTable->find()->count();
        $this->assertEquals(2, $finalCount - $initialCount, 'We should have added 3 new articles');
    }

    protected function _initializeAction($post = [])
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
            'baseUrl' => '/articles_collection/collection/add'
        ];
        $this->Service = ServiceRegistry::get($this->request['service'], $options);

        $this->Action = new AddEditAction([
            'service' => $this->Service,
        ]);
        $this->Action->setTable(TableRegistry::get('Articles'));
    }
}
