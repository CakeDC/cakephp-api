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

use Cake\Core\Configure;
use Cake\Routing\Route\Route;
use CakeDC\Api\Service\ServiceRegistry;
use CakeDC\Api\Service\Utility\ReverseRouting;
use CakeDC\Api\Test\ConfigTrait;
use CakeDC\Api\TestSuite\TestCase;

/**
 * Unit tests for the HATEOAS reverse routing helper.
 */
class ReverseRoutingTest extends TestCase
{
    use ConfigTrait;

    protected function tearDown(): void
    {
        ServiceRegistry::getServiceLocator()->clear();
        Configure::delete('Api.routeBase');
        parent::tearDown();
    }

    public function testLinkWithoutVersion(): void
    {
        Configure::write('Api.routeBase', '/api');
        $rr = new ReverseRouting();
        $result = $rr->link('self', '/articles/1', 'GET', null);
        $this->assertSame('self', $result['name']);
        $this->assertSame('http://localhost/api/articles/1', $result['href']);
        $this->assertSame('/api/articles/1', $result['rel']);
        $this->assertSame('GET', $result['method']);
    }

    public function testLinkWithVersion(): void
    {
        Configure::write('Api.routeBase', '/api');
        $rr = new ReverseRouting();
        $result = $rr->link('edit', '/articles/1', 'PUT', 'v1');
        $this->assertSame('/api/v1/articles/1', $result['rel']);
        $this->assertSame('http://localhost/api/v1/articles/1', $result['href']);
    }

    public function testLinkMethodFromArray(): void
    {
        Configure::write('Api.routeBase', '/api');
        $rr = new ReverseRouting();
        $result = $rr->link('self', '/articles', ['GET', 'POST'], null);
        $this->assertSame('GET', $result['method']);
    }

    public function testLinkWithoutRouteBaseConfig(): void
    {
        $rr = new ReverseRouting();
        $result = $rr->link('self', '/articles', 'GET', null);
        $this->assertSame('/api/articles', $result['rel']);
    }

    public function testCompareDefaultsMatch(): void
    {
        $rr = new ReverseRouting();
        $route1 = [
            'controller' => 'Articles',
            'action' => 'index',
            'plugin' => null,
            '_method' => 'GET',
        ];
        $route2 = [
            'controller' => 'Articles',
            'action' => 'index',
            'plugin' => null,
            '_method' => 'GET',
        ];
        $this->assertTrue($rr->compareDefaults($route1, $route2));
    }

    public function testCompareDefaultsMismatch(): void
    {
        $rr = new ReverseRouting();
        $route1 = [
            'controller' => 'Articles',
            'action' => 'index',
            'plugin' => null,
            '_method' => 'GET',
        ];
        $route2 = [
            'controller' => 'Articles',
            'action' => 'edit',
            'plugin' => null,
            '_method' => 'GET',
        ];
        $this->assertFalse($rr->compareDefaults($route1, $route2));
    }

    public function testCompareDefaultsMethodInArray(): void
    {
        $rr = new ReverseRouting();
        $route1 = [
            'controller' => 'Articles',
            'action' => 'index',
            'plugin' => null,
            '_method' => ['GET', 'POST'],
        ];
        $route2 = [
            'controller' => 'Articles',
            'action' => 'index',
            'plugin' => null,
            '_method' => 'GET',
        ];
        $this->assertTrue($rr->compareDefaults($route1, $route2));
    }

    public function testFindRoute(): void
    {
        $rr = new ReverseRouting();
        $routes = [
            new Route('/articles', ['controller' => 'Articles', 'action' => 'index', '_method' => 'GET', 'plugin' => null]),
            new Route('/articles/:id', ['controller' => 'Articles', 'action' => 'view', '_method' => 'GET', 'plugin' => null]),
        ];
        $route = $rr->findRoute([
            'controller' => 'Articles',
            'action' => 'view',
            'plugin' => null,
            '_method' => 'GET',
        ], $routes);
        $this->assertInstanceOf(Route::class, $route);
        $this->assertSame('Articles', $route->defaults['controller']);
        $this->assertSame('view', $route->defaults['action']);
    }

    public function testFindRouteReturnsNull(): void
    {
        $rr = new ReverseRouting();
        $routes = [
            new Route('/articles', ['controller' => 'Articles', 'action' => 'index', '_method' => 'GET', 'plugin' => null]),
        ];
        $route = $rr->findRoute([
            'controller' => 'Posts',
            'action' => 'index',
            'plugin' => null,
            '_method' => 'GET',
        ], $routes);
        $this->assertNull($route);
    }

    public function testIndexPathRootService(): void
    {
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

        $rr = new ReverseRouting();
        $path = $rr->indexPath($service->getAction());
        $this->assertIsString($path);
        $this->assertStringContainsString('articles', $path);
    }
}
