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

namespace CakeDC\Api\Test\TestCase\Auth\Authenticate;

use CakeDC\Api\Service\Action\CrudIndexAction;
use CakeDC\Api\Service\Auth\Authenticate\TokenAuthenticate;
use CakeDC\Api\Service\FallbackService;
use CakeDC\Api\Test\ConfigTrait;
use CakeDC\Api\Test\FixturesTrait;
use Cake\Network\Request;
use Cake\Network\Response;
use CakeDC\Api\TestSuite\TestCase;

class TokenAuthenticateTest extends TestCase
{

    use ConfigTrait;
    use FixturesTrait;

    /**
     * @var TokenAuthenticate
     */
    public $token;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    public function setUp()
    {
        $request = new Request();
        $response = new Response();
        $service = new FallbackService([
            'request' => $request,
            'response' => $response
        ]);
        $action = new CrudIndexAction([
            'service' => $service,
            'request' => $request,
            'response' => $response
        ]);
        $this->token = new TokenAuthenticate($action, ['require_ssl' => false]);
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    public function tearDown()
    {
        unset($this->token, $this->controller);
    }

    /**
     * test
     *
     * @return void
     */
    public function testAuthenticateHappy()
    {
        $request = new Request('/?token=yyy');
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
        $request = new Request('/');
        $result = $this->token->authenticate($request, new Response());
        $this->assertFalse($result);

        $request = new Request('/?token=none');
        $result = $this->token->authenticate($request, new Response());
        $this->assertFalse($result);

        $request = new Request('/?token=');
        $result = $this->token->authenticate($request, new Response());
        $this->assertFalse($result);
    }

    /**
     * test
     *
     * @expectedException \OutOfBoundsException
     * @expectedExceptionMessage Type wrong is not valid
     *
     */
    public function testAuthenticateWrongType()
    {
        $this->token->config('type', 'wrong');
        $request = new Request('/');
        $this->token->authenticate($request, new Response());
    }

    /**
     * test
     *
     * @expectedException \Cake\Network\Exception\ForbiddenException
     * @expectedExceptionMessage SSL is required for ApiKey Authentication
     *
     */
    public function testAuthenticateRequireSSL()
    {
        $this->token->config('require_ssl', true);
        $request = new Request('/?token=test');
        $this->token->authenticate($request, new Response());
    }

    /**
     * test
     *
     */
    public function testAuthenticateRequireSSLNoKey()
    {
        $this->token->config('require_ssl', true);
        $request = new Request('/');
        $this->assertFalse($this->token->authenticate($request, new Response()));
    }


    /**
     * test
     *
     * @return void
     */
    public function testHeaderHappy()
    {
        $request = $this->getMockBuilder('\Cake\Network\Request')
            ->setMethods(['header'])
            ->getMock();
        $request->expects($this->once())
            ->method('header')
            ->with('token')
            ->will($this->returnValue('yyy'));
        $this->token->config('type', 'header');
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
        $request = $this->getMockBuilder('\Cake\Network\Request')
            ->setMethods(['header'])
            ->getMock();
        $request->expects($this->once())
            ->method('header')
            ->with('token')
            ->will($this->returnValue('wrong'));
        $this->token->config('type', 'header');
        $result = $this->token->authenticate($request, new Response());
        $this->assertFalse($result);
    }
}
