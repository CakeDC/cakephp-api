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

namespace CakeDC\Api\Test\TestCase\Auth\Authenticate;

use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use CakeDC\Api\Service\Action\CrudIndexAction;
use CakeDC\Api\Service\Auth\Authenticate\TokenAuthenticate;
use CakeDC\Api\Service\FallbackService;
use CakeDC\Api\Test\ConfigTrait;
use CakeDC\Api\TestSuite\TestCase;

class TokenAuthenticateTest extends TestCase
{
    use ConfigTrait;

    public $controller;

    public \CakeDC\Api\Service\Auth\Authenticate\TokenAuthenticate $token;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    public function setUp(): void
    {
        parent::setUp();
        $request = new ServerRequest();
        $response = new Response();
        $service = new FallbackService([
            'request' => $request,
            'response' => $response,
        ]);
        $action = new CrudIndexAction([
            'service' => $service,
            'request' => $request,
            'response' => $response,
        ]);
        $this->token = new TokenAuthenticate($action, ['require_ssl' => false]);
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    public function tearDown(): void
    {
        unset($this->token, $this->controller);
        parent::tearDown();
    }

    /**
     * test
     *
     * @return void
     */
    public function testAuthenticateHappy()
    {
        $request = new ServerRequest(['url' => '/?token=yyy']);
        $result = $this->token->authenticate($request, new Response());
        $this->assertEquals('user-1', $result['username']);
    }

    /**
     * test
     *
     * @return void
     */
    public function testAuthenticateFail()
    {
        $request = new ServerRequest(['url' => '/']);
        $result = $this->token->authenticate($request, new Response());
        $this->assertFalse($result);

        $request = new ServerRequest(['url' => '/?token=none']);
        $result = $this->token->authenticate($request, new Response());
        $this->assertFalse($result);

        $request = new ServerRequest(['url' => '/?token=']);
        $result = $this->token->authenticate($request, new Response());
        $this->assertFalse($result);
    }

    /**
     * test
     */
    public function testAuthenticateWrongType()
    {
        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage('Type wrong is not valid');
        $this->token->setConfig('type', 'wrong');
        $request = new ServerRequest(['url' => '/']);
        $this->token->authenticate($request, new Response());
    }

    /**
     * test
     */
    public function testAuthenticateRequireSSL()
    {
        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage('SSL is required for ApiKey Authentication');
        $this->token->setConfig('require_ssl', true);
        $request = new ServerRequest(['url' => '/?token=test']);
        $this->token->authenticate($request, new Response());
    }

    /**
     * test
     */
    public function testAuthenticateRequireSSLNoKey()
    {
        $this->token->setConfig('require_ssl', true);
        $request = new ServerRequest(['url' => '/']);
        $this->assertFalse($this->token->authenticate($request, new Response()));
    }

    /**
     * test
     *
     * @return void
     */
    public function testHeaderHappy()
    {
        $request = $this->getMockBuilder(\Cake\Http\ServerRequest::class)
            ->setMethods(['getHeader'])
            ->getMock();
        $request->expects($this->once())
            ->method('getHeader')
            ->with('token')
            ->will($this->returnValue(['yyy']));
        $this->token->setConfig('type', 'header');
        $result = $this->token->authenticate($request, new Response());
        $this->assertEquals('user-1', $result['username']);
    }

    /**
     * test
     *
     * @return void
     */
    public function testAuthenticateHeaderFail()
    {
        $request = $this->getMockBuilder(\Cake\Http\ServerRequest::class)
            ->setMethods(['getHeader'])
            ->getMock();
        $request->expects($this->once())
            ->method('getHeader')
            ->with('token')
            ->will($this->returnValue(['wrong']));
        $this->token->setConfig('type', 'header');
        $result = $this->token->authenticate($request, new Response());
        $this->assertFalse($result);
    }
}
