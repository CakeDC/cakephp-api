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

namespace CakeDC\Api\Test\TestCase\Auth\Authorize;

use Cake\Core\Exception\CakeException;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use CakeDC\Api\Service\Action\CrudIndexAction;
use CakeDC\Api\Service\Auth\Authorize\SimpleRbacAuthorize;
use CakeDC\Api\Service\FallbackService;
use CakeDC\Api\TestSuite\TestCase;
use CakeDC\Users\Auth\Rules\Rule;
use ReflectionClass;

class SimpleRbacAuthorizeTest extends TestCase
{
    public \CakeDC\Api\Service\FallbackService $Service;

    public \CakeDC\Api\Service\Action\CrudIndexAction $Action;

    /**
     * @var SimpleRbacAuthorize
     */
    protected $simpleRbacAuthorize;

    protected array $defaultPermissions = [
        [
            'role' => 'admin',
            'version' => '*',
            'service' => '*',
            'action' => '*',
        ],
    ];

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    public function setUp(): void
    {
        $request = new ServerRequest();
        $response = new Response();

        $this->Service = new FallbackService([
            'request' => $request,
            'response' => $response,
        ]);
        $this->Action = new CrudIndexAction([
            'service' => $this->Service,
        ]);
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    public function tearDown(): void
    {
        unset($this->simpleRbacAuthorize, $this->Service, $this->Action);
    }

    /**
     * @covers CakeDC\Api\Service\Auth\Authorize\SimpleRbacAuthorize::__construct
     */
    public function testConstruct()
    {
        //don't autoload config
        $this->simpleRbacAuthorize = new SimpleRbacAuthorize($this->Action, ['autoload_config' => false]);
        $this->assertEmpty($this->simpleRbacAuthorize->getConfig('permissions'));
    }

    /**
     * test
     *
     * @return void
     */
    public function testLoadPermissions()
    {
        $this->simpleRbacAuthorize = $this->getMockBuilder(\CakeDC\Api\Service\Auth\Authorize\SimpleRbacAuthorize::class)
                ->disableOriginalConstructor()
                ->getMock();
        $reflectedClass = new ReflectionClass($this->simpleRbacAuthorize);
        $loadPermissions = $reflectedClass->getMethod('_loadPermissions');
        $loadPermissions->setAccessible(true);
        $this->expectException(CakeException::class);
        $permissions = $loadPermissions->invoke($this->simpleRbacAuthorize, 'missing');
        $this->assertEquals($this->defaultPermissions, $permissions);
    }

    /**
     * @covers CakeDC\Api\Service\Auth\Authorize\SimpleRbacAuthorize::__construct
     */
    public function testConstructMissingPermissionsFile()
    {
        $this->expectException(CakeException::class);
        $this->simpleRbacAuthorize = $this->getMockBuilder(\CakeDC\Api\Service\Auth\Authorize\SimpleRbacAuthorize::class)
            ->setMethods(null)
            ->setConstructorArgs([$this->Action, ['autoload_config' => 'does-not-exist']])
            ->getMock();
        //we should have the default permissions
        $this->assertEquals($this->defaultPermissions, $this->simpleRbacAuthorize->getConfig('permissions'));
    }

    protected function assertConstructorPermissions($instance, $config, $permissions)
    {
        $reflectedClass = new ReflectionClass($instance);
        $constructor = $reflectedClass->getConstructor();
        $constructor->invoke($this->simpleRbacAuthorize, $this->Action, $config);

        //we should have the default permissions
        $resultPermissions = $this->simpleRbacAuthorize->getConfig('permissions');
        $this->assertEquals($permissions, $resultPermissions);
    }

    /**
     * @covers CakeDC\Api\Service\Auth\Authorize\SimpleRbacAuthorize::__construct
     */
    public function testConstructPermissionsFileHappy()
    {
        $permissions = [[
            'service' => 'Test',
            'action' => 'test',
        ]];
        $className = \CakeDC\Api\Service\Auth\Authorize\SimpleRbacAuthorize::class;
        $this->simpleRbacAuthorize = $this->getMockBuilder($className)
                ->setMethods(['_loadPermissions'])
                ->disableOriginalConstructor()
                ->getMock();
        $this->simpleRbacAuthorize
                ->expects($this->once())
                ->method('_loadPermissions')
                ->with('permissions-happy')
                ->will($this->returnValue($permissions));
        $this->assertConstructorPermissions($className, ['autoload_config' => 'permissions-happy'], $permissions);
    }

    protected function preparePermissions($permissions)
    {
        $className = \CakeDC\Api\Service\Auth\Authorize\SimpleRbacAuthorize::class;
        $simpleRbacAuthorize = $this->getMockBuilder($className)
                ->setMethods(['_loadPermissions'])
                ->disableOriginalConstructor()
                ->getMock();
        $simpleRbacAuthorize->setConfig('permissions', $permissions);

        return $simpleRbacAuthorize;
    }

    /**
     * @dataProvider providerAuthorize
     * @param $permissions
     * @param $user
     * @param $requestParams
     * @param $expected
     * @param string|null $msg
     */
    public function testAuthorize($permissions, $user, $requestParams, $expected, $msg = null)
    {
        $this->simpleRbacAuthorize = $this->preparePermissions($permissions);
        $request = new ServerRequest();
        $response = new Response();
        $service = new FallbackService([
            'request' => $request,
            'response' => $response,
            'service' => $requestParams['service'],
        ]);
        $action = new CrudIndexAction([
            'service' => $service,
            'name' => $requestParams['action'],
        ]);

        $this->simpleRbacAuthorize->setAction($action);

        $msg = (string)$msg;
        $result = $this->simpleRbacAuthorize->authorize($user, $request);
        $this->assertSame($expected, $result, $msg);
    }

    public function providerAuthorize()
    {
        $trueRuleMock = $this->getMockBuilder(Rule::class)
            ->setMethods(['allowed'])
            ->getMock();
        $trueRuleMock->expects($this->any())
            ->method('allowed')
            ->willReturn(true);

        return [
            'happy-strict-all' => [
                //permissions
                [[
                    'role' => 'test',
                    'service' => 'tests',
                    'action' => 'test',
                    'allowed' => true,
                ]],
                //user
                [
                    'id' => 1,
                    'username' => 'luke',
                    'role' => 'test',
                ],
                //request
                [
                    'service' => 'tests',
                    'action' => 'test',
                ],
                //expected
                true,
            ],
            'happy-strict-all-deny' => [
                //permissions
                [[
                    'role' => 'test',
                    'service' => 'tests',
                    'action' => 'test',
                    'allowed' => false,
                ]],
                //user
                [
                    'id' => 1,
                    'username' => 'luke',
                    'role' => 'test',
                ],
                //request
                [
                    'service' => 'tests',
                    'action' => 'test',
                ],
                //expected
                false,
            ],
            'happy-pl-null-allowed-null' => [
                //permissions
                [[
                    'role' => 'test',
                    'service' => 'tests',
                    'action' => 'test',
                ]],
                //user
                [
                    'id' => 1,
                    'username' => 'luke',
                    'role' => 'test',
                ],
                //request
                [
                    'service' => 'tests',
                    'action' => 'test',
                ],
                //expected
                true,
            ],
            'happy-asterisk' => [
                //permissions
                [[
                    'role' => 'test',
                    'service' => 'tests',
                    'action' => 'test',
                ]],
                //user
                [
                    'id' => 1,
                    'username' => 'luke',
                    'role' => 'test',
                ],
                //request
                [
                    'service' => 'tests',
                    'action' => 'test',
                ],
                //expected
                true,
            ],
            'happy-asterisk-main-app' => [
                //permissions
                [[
                    'role' => 'test',
                    'service' => 'tests',
                    'action' => 'test',
                ]],
                //user
                [
                    'id' => 1,
                    'username' => 'luke',
                    'role' => 'test',
                ],
                //request
                [
                    'service' => 'tests',
                    'action' => 'test',
                ],
                //expected
                true,
            ],
            'happy-role-asterisk' => [
                //permissions
                [[
                    'role' => '*',
                    'service' => 'tests',
                    'action' => 'test',
                ]],
                //user
                [
                    'id' => 1,
                    'username' => 'luke',
                    'role' => 'any-role',
                ],
                //request
                [
                    'service' => 'tests',
                    'action' => 'test',
                ],
                //expected
                true,
            ],
            'happy-service-asterisk' => [
                //permissions
                [[
                    'role' => 'test',
                    'service' => '*',
                    'action' => 'test',
                ]],
                //user
                [
                    'id' => 1,
                    'username' => 'luke',
                    'role' => 'test',
                ],
                //request
                [
                    'service' => 'tests',
                    'action' => 'test',
                ],
                //expected
                true,
            ],
            'happy-action-asterisk' => [
                //permissions
                [[
                    'role' => 'test',
                    'service' => 'tests',
                    'action' => '*',
                ]],
                //user
                [
                    'id' => 1,
                    'username' => 'luke',
                    'role' => 'test',
                ],
                //request
                [
                    'service' => 'tests',
                    'action' => 'any',
                ],
                //expected
                true,
            ],
            'happy-some-asterisk-allowed' => [
                //permissions
                [[
                    'role' => 'test',
                    'service' => '*',
                    'action' => '*',
                ]],
                //user
                [
                    'id' => 1,
                    'username' => 'luke',
                    'role' => 'test',
                ],
                //request
                [
                    'service' => 'tests',
                    'action' => 'any',
                ],
                //expected
                true,
            ],
            'happy-some-asterisk-deny' => [
                //permissions
                [[
                    'role' => 'test',
                    'service' => '*',
                    'action' => '*',
                    'allowed' => false,
                ]],
                //user
                [
                    'id' => 1,
                    'username' => 'luke',
                    'role' => 'test',
                ],
                //request
                [
                    'service' => 'tests',
                    'action' => 'any',
                ],
                //expected
                false,
            ],
            'all-deny' => [
                //permissions
                [[
                    'role' => '*',
                    'service' => '*',
                    'action' => '*',
                    'allowed' => false,
                ]],
                //user
                [
                    'id' => 1,
                    'username' => 'luke',
                    'role' => 'test',
                ],
                //request
                [
                    'service' => 'Any',
                    'action' => 'any',
                ],
                //expected
                false,
            ],
            'dasherized' => [
                //permissions
                [[
                    'role' => 'test',
                    'service' => 'TestTests',
                    'action' => 'TestAction',
                    'allowed' => true,
                ]],
                //user
                [
                    'id' => 1,
                    'username' => 'luke',
                    'role' => 'test',
                ],
                //request
                [
                    'service' => 'test-tests',
                    'action' => 'test-action',
                ],
                //expected
                true,
            ],
            'happy-array' => [
                //permissions
                [[
                    'role' => ['test'],
                    'service' => ['tests'],
                    'action' => ['one', 'two'],
                ]],
                //user
                [
                    'id' => 1,
                    'username' => 'luke',
                    'role' => 'test',
                ],
                //request
                [
                    'service' => 'tests',
                    'action' => 'one',
                ],
                //expected
                true,
            ],
            'happy-array' => [
                //permissions
                [[
                    'role' => ['test'],
                    'service' => ['tests'],
                    'action' => ['one', 'two'],
                ]],
                //user
                [
                    'id' => 1,
                    'username' => 'luke',
                    'role' => 'test',
                ],
                //request
                [
                    'service' => 'tests',
                    'action' => 'three',
                ],
                //expected
                false,
            ],
            'happy-callback-check-params' => [
                //permissions
                [[
                    'role' => ['test'],
                    'service' => ['tests'],
                    'action' => ['one', 'two'],
                    'allowed' => fn($user, $role, $request) => $user['id'] === 1 && $role == 'test',
                ]],
                //user
                [
                    'id' => 1,
                    'username' => 'luke',
                    'role' => 'test',
                ],
                //request
                [
                    'service' => 'tests',
                    'action' => 'one',
                ],
                //expected
                true,
            ],
            'happy-callback-deny' => [
                //permissions
                [[
                    'role' => ['test'],
                    'service' => ['tests'],
                    'action' => ['one', 'two'],
                    'allowed' => fn($user, $role, $request) => false,
                ]],
                //user
                [
                    'id' => 1,
                    'username' => 'luke',
                    'role' => 'test',
                ],
                //request
                [
                    'service' => 'tests',
                    'action' => 'one',
                ],
                //expected
                false,
            ],
            'happy-prefix' => [
                //permissions
                [[
                    'role' => ['test'],
                    'prefix' => ['admin'],
                    'service' => ['tests'],
                    'action' => ['one', 'two'],
                ]],
                //user
                [
                    'id' => 1,
                    'username' => 'luke',
                    'role' => 'test',
                ],
                //request
                [
                    'prefix' => 'admin',
                    'service' => 'tests',
                    'action' => 'one',
                ],
                //expected
                true,
            ],

            'rule-class' => [
                //permissions
                [
                    [
                        'role' => ['test'],
                        'service' => '*',
                        'action' => 'one',
                        'allowed' => $trueRuleMock,
                    ],
                ],
                //user
                [
                    'id' => 1,
                    'username' => 'luke',
                    'role' => 'test',
                ],
                //request
                [
                    'service' => 'tests',
                    'action' => 'one',
                ],
                //expected
                true,
            ],
        ];
    }
}
