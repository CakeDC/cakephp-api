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

use Cake\Routing\Exception\MissingRouteException;
use Cake\Routing\Route\Route;
use CakeDC\Api\Service\ServiceRegistry;
use CakeDC\Api\Test\ConfigTrait;
use CakeDC\Api\TestSuite\TestCase;

/**
 * Unit tests for the service-level route parsing and URL helpers.
 */
class ServiceRoutingTest extends TestCase
{
    use ConfigTrait;

    protected function tearDown(): void
    {
        ServiceRegistry::getServiceLocator()->clear();
        parent::tearDown();
    }

    protected function buildService(string $serviceName = 'articles', string $baseUrl = '/articles', array $params = []): \CakeDC\Api\Service\Service
    {
        ServiceRegistry::getServiceLocator()->clear();
        $this->_initializeRequest([
            'params' => ['service' => $serviceName] + $params,
        ], 'GET');

        return ServiceRegistry::getServiceLocator()->get($serviceName, [
            'version' => null,
            'service' => $serviceName,
            'request' => $this->request,
            'response' => $this->response,
            'baseUrl' => $baseUrl,
        ]);
    }

    public function testParseRouteCollection(): void
    {
        $service = $this->buildService();
        $route = $service->parseRoute('/articles');
        $this->assertSame('articles', $route['controller']);
        $this->assertSame('index', $route['action']);
    }

    public function testParseRouteItem(): void
    {
        $service = $this->buildService();
        $route = $service->parseRoute('/articles/5');
        $this->assertSame('articles', $route['controller']);
        $this->assertSame('view', $route['action']);
        $this->assertSame('5', $route['pass'][0]);
    }

    public function testParseRouteInvalidThrows(): void
    {
        $this->expectException(MissingRouteException::class);
        $service = $this->buildService();
        $service->parseRoute('/nope');
    }

    public function testRoutesReturnsRouteList(): void
    {
        $service = $this->buildService();
        $routes = $service->routes();
        $this->assertNotEmpty($routes);
        foreach ($routes as $route) {
            $this->assertInstanceOf(Route::class, $route);
        }
    }

    public function testRouteUrl(): void
    {
        $service = $this->buildService();
        $route = $service->parseRoute('/articles');
        unset($route['pass']);
        $url = $service->routeUrl($route);
        $this->assertIsString($url);
        $this->assertStringContainsString('articles', $url);
    }

    public function testInnerServiceResolvedWithParent(): void
    {
        $service = $this->buildService('authors', '/authors/1/articles', [
            'pass' => ['1', 'articles'],
        ]);
        $service->dispatchPrepareAction();

        $action = $service->getAction();
        $inner = $action->getService();
        $this->assertSame('articles', $inner->getName());
        $this->assertSame($service, $inner->getParentService());
    }

    public function testGetParentServiceNullForRoot(): void
    {
        $service = $this->buildService();
        $this->assertNull($service->getParentService());
    }
}
