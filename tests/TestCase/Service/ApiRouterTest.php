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

use Cake\Http\ServerRequest;
use CakeDC\Api\Routing\ApiRouter;
use CakeDC\Api\Service\ServiceRegistry;
use CakeDC\Api\Test\ConfigTrait;
use CakeDC\Api\TestSuite\TestCase;

/**
 * Unit tests for the ApiRouter request parsing against service resource routes.
 */
class ApiRouterTest extends TestCase
{
    use ConfigTrait;

    protected function tearDown(): void
    {
        ServiceRegistry::getServiceLocator()->clear();
        parent::tearDown();
    }

    protected function parseUrl(string $url, string $method = 'GET'): array
    {
        ApiRouter::reload();
        $this->_initializeRequest([
            'params' => ['service' => 'articles'],
        ], $method);
        $service = ServiceRegistry::getServiceLocator()->get('articles', [
            'version' => null,
            'service' => 'articles',
            'request' => $this->request,
            'response' => $this->response,
            'baseUrl' => '/articles',
        ]);
        $service->loadRoutes();

        $request = new ServerRequest([
            'url' => $url,
            'environment' => ['REQUEST_METHOD' => $method],
        ]);

        return ApiRouter::parseRequest($request);
    }

    public function testParseIndexRoute(): void
    {
        $params = $this->parseUrl('/articles');
        $this->assertSame('articles', $params['controller']);
        $this->assertSame('index', $params['action']);
    }

    public function testParseItemRoute(): void
    {
        $params = $this->parseUrl('/articles/5');
        $this->assertSame('articles', $params['controller']);
        $this->assertSame('view', $params['action']);
        $this->assertSame('5', $params['pass'][0]);
    }

    public function testParseCreateRouteWithPost(): void
    {
        $params = $this->parseUrl('/articles', 'POST');
        $this->assertSame('articles', $params['controller']);
        $this->assertSame('add', $params['action']);
    }
}
