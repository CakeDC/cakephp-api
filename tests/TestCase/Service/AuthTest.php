<?php
declare(strict_types=1);

/**
 * Copyright 2016 - 2026, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2016 - 2026, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Api\Test\TestCase\Service;

use CakeDC\Api\Service\Auth\Auth;
use CakeDC\Api\Service\ServiceRegistry;
use CakeDC\Api\Test\ConfigTrait;
use CakeDC\Api\TestSuite\TestCase;
use ReflectionMethod;

/**
 * Unit tests for the legacy Auth allow/deny logic.
 *
 * NOTE: This is the Cake2/3-era auth layer, kept for backward compatibility.
 */
class AuthTest extends TestCase
{
    use ConfigTrait;

    protected function tearDown(): void
    {
        ServiceRegistry::getServiceLocator()->clear();
        parent::tearDown();
    }

    protected function buildAuth(string $actionName = 'index'): Auth
    {
        ServiceRegistry::getServiceLocator()->clear();
        $this->_initializeRequest([
            'params' => ['service' => 'articles'],
        ], 'GET');
        $service = ServiceRegistry::getServiceLocator()->get('articles', [
            'version' => null,
            'service' => 'articles',
            'request' => $this->request,
            'response' => $this->response,
            'baseUrl' => '/articles',
        ]);
        $service->dispatchPrepareAction();

        $action = $service->getAction()->setName($actionName);

        return new Auth([
            'request' => $this->request,
            'response' => $this->response,
            'service' => $service,
            'action' => $action,
        ]);
    }

    protected function isAllowed(Auth $auth, string $actionName): bool
    {
        $method = new ReflectionMethod(Auth::class, 'isAllowed');

        $action = $auth->action->setName($actionName);

        return (bool)$method->invoke($auth, $action);
    }

    public function testDefaults(): void
    {
        $auth = $this->buildAuth();
        $this->assertSame('Memory', $auth->getConfig('storage'));
        $this->assertSame('identity', $auth->getConfig('identityAttribute'));
    }

    public function testAllow(): void
    {
        $auth = $this->buildAuth();
        $auth->allow(['index', 'view']);
        $this->assertSame(['index', 'view'], $auth->allowedActions);
        $this->assertTrue($this->isAllowed($auth, 'index'));
        $this->assertTrue($this->isAllowed($auth, 'view'));
        $this->assertFalse($this->isAllowed($auth, 'edit'));
    }

    public function testAllowAll(): void
    {
        $auth = $this->buildAuth();
        $auth->allow('*');
        $this->assertTrue($this->isAllowed($auth, 'index'));
        $this->assertTrue($this->isAllowed($auth, 'delete'));
    }

    public function testCaseInsensitiveMatch(): void
    {
        $auth = $this->buildAuth();
        $auth->allow('Index');
        $this->assertTrue($this->isAllowed($auth, 'index'));
    }

    public function testDenyRemovesAction(): void
    {
        $auth = $this->buildAuth();
        $auth->allow(['index', 'view']);
        $auth->deny('index');
        $this->assertSame(['view'], $auth->allowedActions);
        $this->assertFalse($this->isAllowed($auth, 'index'));
        $this->assertTrue($this->isAllowed($auth, 'view'));
    }

    public function testDenyAllClearsList(): void
    {
        $auth = $this->buildAuth();
        $auth->allow(['index', 'view']);
        $auth->deny();
        $this->assertSame([], $auth->allowedActions);
        $this->assertFalse($this->isAllowed($auth, 'index'));
    }

    public function testRequestResponseSetters(): void
    {
        $auth = $this->buildAuth();
        $this->assertSame($this->request, $auth->getRequest());
        $this->assertSame($this->response, $auth->getResponse());
        $auth->setRequest(null);
        $auth->setResponse(null);
        $this->assertNull($auth->getRequest());
        $this->assertNull($auth->getResponse());
    }

    public function testMagicGetSet(): void
    {
        $auth = $this->buildAuth();
        $this->assertNull($auth->someUnknownProperty);
        $auth->allowedActions = ['index'];
        $this->assertSame(['index'], $auth->allowedActions);
    }

    public function testServiceActionProperties(): void
    {
        $auth = $this->buildAuth();
        $this->assertInstanceOf(\CakeDC\Api\Service\Service::class, $auth->service);
        $this->assertInstanceOf(\CakeDC\Api\Service\Action\Action::class, $auth->action);
    }
}
