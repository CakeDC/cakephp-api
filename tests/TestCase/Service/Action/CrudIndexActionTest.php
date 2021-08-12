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

use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\ORM\ResultSet;
use CakeDC\Api\Service\Action\CrudIndexAction;
use CakeDC\Api\Service\FallbackService;
use CakeDC\Api\Test\ConfigTrait;
use CakeDC\Api\TestSuite\TestCase;

class CrudIndexActionTest extends TestCase
{
    use ConfigTrait;

    public \CakeDC\Api\Service\Action\CrudIndexAction $Action;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $request = new ServerRequest();
        $response = new Response();
        $service = new FallbackService([
            'request' => $request,
            'response' => $response,
            'service' => 'articles',
        ]);

        $this->Action = new CrudIndexAction([
            'service' => $service,
            'request' => $request,
            'response' => $response,
        ]);
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
    public function testExecute()
    {
        $onFindEntities = $afterFindEntities = false;
        $this->Action->getEventManager()->on('Action.Crud.onFindEntities', function () use (&$onFindEntities) {
            $onFindEntities = true;
        });
        $this->Action->getEventManager()->on('Action.Crud.afterFindEntities', function () use (&$afterFindEntities) {
            $afterFindEntities = true;
        });

        $result = $this->Action->execute();
        $this->assertTrue($result instanceof ResultSet);
        $this->assertTrue($onFindEntities);
        $this->assertTrue($afterFindEntities);
    }
}
