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
use CakeDC\Api\Service\Auth\Authenticate\SocialAuthenticate;
use CakeDC\Api\Service\Auth\Authenticate\TokenAuthenticate;
use CakeDC\Api\Service\FallbackService;
use CakeDC\Api\TestSuite\TestCase;
use CakeDC\Api\Test\ConfigTrait;
use CakeDC\Api\Test\FixturesTrait;
use Cake\Network\Request;
use Cake\Network\Response;

class SocialAuthenticateTest extends TestCase
{

    use ConfigTrait;
    use FixturesTrait;

    /**
     * @var SocialAuthenticate
     */
    public $social;

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
        $this->social = new SocialAuthenticate($action, ['require_ssl' => false]);
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    public function tearDown()
    {
        unset($this->social, $this->controller);
    }

    /**
     * test
     *
     * @return void
     */
    public function testAuthenticateHappy()
    {
        $request = new Request('/?provider=Facebook&token=token-1234&token_secret=token-secret');
        $result = $this->social->authenticate($request, new Response());
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
        $result = $this->social->authenticate($request, new Response());
        $this->assertFalse($result);

        $request = new Request('/?provider=Facebook&token=none');
        $result = $this->social->authenticate($request, new Response());
        $this->assertFalse($result);

        $request = new Request('/?provider=Facebook&token=');
        $result = $this->social->authenticate($request, new Response());
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
        $this->social->setConfig('type', 'wrong');
        $request = new Request('/');
        $this->social->authenticate($request, new Response());
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
        $this->social->setConfig('require_ssl', true);
        $request = new Request('/?token=token-1234&token_secret=token-secret&provider=Facebook');
        $this->social->authenticate($request, new Response());
    }

    /**
     * test
     *
     */
    public function testAuthenticateRequireSSLNoKey()
    {
        $this->social->setConfig('require_ssl', true);
        $request = new Request('/');
        $this->assertFalse($this->social->authenticate($request, new Response()));
    }


    /**
     * test
     *
     * @return void
     */
    public function testHeaderHappy()
    {
        $request = $this->getMockBuilder('\Cake\Http\ServerRequest')
            ->setMethods(['getHeader'])
            ->getMock();

        $request->expects($this->at(0))
            ->method('getHeader')
            ->with('provider')
            ->will($this->returnValue('Facebook'));

        $request->expects($this->at(1))
            ->method('getHeader')
            ->with('token')
            ->will($this->returnValue('token-1234'));

        $request->expects($this->at(2))
            ->method('getHeader')
            ->with('token_secret')
            ->will($this->returnValue('token-secret'));
        $this->social->setConfig('type', 'header');
        $result = $this->social->authenticate($request, new Response());
        $this->assertEquals('user-1', $result['username']);
    }

    /**
     * test
     *
     * @return void
     */
    public function testAuthenticateHeaderFail()
    {
        $request = $this->getMockBuilder('\Cake\Http\ServerRequest')
            ->setMethods(['getHeader'])
            ->getMock();
        $request->expects($this->at(0))
            ->method('getHeader')
            ->with('provider')
            ->will($this->returnValue('wrong'));

        $request->expects($this->at(1))
            ->method('getHeader')
            ->with('token')
            ->will($this->returnValue('wrong'));

        $request->expects($this->at(2))
            ->method('getHeader')
            ->with('token_secret')
            ->will($this->returnValue('wrong'));
        $this->social->setConfig('type', 'header');
        $result = $this->social->authenticate($request, new Response());
        $this->assertFalse($result);
    }
}
