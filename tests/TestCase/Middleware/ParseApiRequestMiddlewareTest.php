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

namespace CakeDC\Api\Test\TestCase\Middleware;

use Cake\Core\Configure;
use Cake\Http\Response;
use CakeDC\Api\Middleware\ParseApiRequestMiddleware;
use CakeDC\Api\Service\Service;
use CakeDC\Api\Service\ServiceRegistry;
use CakeDC\Api\Test\ConfigTrait;
use CakeDC\Api\TestSuite\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Unit tests for the API request parsing middleware.
 */
class ParseApiRequestMiddlewareTest extends TestCase
{
    use ConfigTrait;

    protected function setUp(): void
    {
        parent::setUp();
        Configure::delete('Api.prefix');
        Configure::write('Api.useVersioning', false);
    }

    protected function tearDown(): void
    {
        ServiceRegistry::getServiceLocator()->clear();
        Configure::delete('Api.prefix');
        Configure::delete('Api.useVersioning');
        parent::tearDown();
    }

    protected function captureHandler(&$capturedService): RequestHandlerInterface
    {
        return new class ($capturedService) implements RequestHandlerInterface {
            public function __construct(public mixed &$service)
            {
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->service = $request->getAttribute('service');
                $response = new Response();
                $response = $response->withStringBody('HANDLER');

                return $response;
            }
        };
    }

    public function testNonApiPathDelegatesToHandler(): void
    {
        $middleware = new ParseApiRequestMiddleware();
        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response();
            }
        };
        $request = new \Cake\Http\ServerRequest(['url' => '/non-api/path']);
        $response = $middleware->process($request, $handler);
        $this->assertInstanceOf(Response::class, $response);
    }

    public function testApiPathAttachesServiceToHandler(): void
    {
        $captured = null;
        $handler = $this->captureHandler($captured);
        $middleware = new ParseApiRequestMiddleware();
        $request = new \Cake\Http\ServerRequest(['url' => '/api/articles']);
        $response = $middleware->process($request, $handler);
        $this->assertInstanceOf(Service::class, $captured);
        $this->assertSame('articles', $captured->getName());
        $this->assertSame('HANDLER', (string)$response->getBody());
    }

    public function testCustomPrefix(): void
    {
        Configure::write('Api.prefix', 'rest');
        $captured = null;
        $handler = $this->captureHandler($captured);
        $middleware = new ParseApiRequestMiddleware();
        $request = new \Cake\Http\ServerRequest(['url' => '/rest/articles']);
        $response = $middleware->process($request, $handler);
        $this->assertInstanceOf(Service::class, $captured);
        $this->assertSame('HANDLER', (string)$response->getBody());
    }

    public function testUnknownServiceReturnsResponse(): void
    {
        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                throw new \RuntimeException('handler should not be called');
            }
        };
        $middleware = new ParseApiRequestMiddleware();
        $request = new \Cake\Http\ServerRequest(['url' => '/api/nonexistent']);
        $response = $middleware->process($request, $handler);
        $this->assertInstanceOf(Response::class, $response);
    }

    public function testActionBuildErrorRespondsDirectly(): void
    {
        // DELETE is not mapped on the collection route → action build fails with
        // a route-not-found error → dispatchPrepareAction returns a Result →
        // middleware responds directly (JSend: HTTP 200, code in the body)
        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                throw new \RuntimeException('handler should not be called');
            }
        };
        $middleware = new ParseApiRequestMiddleware();
        $request = new \Cake\Http\ServerRequest([
            'url' => '/api/articles',
            'environment' => ['REQUEST_METHOD' => 'DELETE'],
        ]);
        $response = $middleware->process($request, $handler);
        $this->assertInstanceOf(Response::class, $response);
        $decoded = json_decode((string)$response->getBody(), true);
        $this->assertSame('error', $decoded['status']);
        $this->assertSame(404, $decoded['code']);
    }

    public function testVersionedServiceResolved(): void
    {
        Configure::write('Api.useVersioning', true);
        $captured = null;
        $handler = $this->captureHandler($captured);
        $middleware = new ParseApiRequestMiddleware();
        $request = new \Cake\Http\ServerRequest(['url' => '/api/v1/articles']);
        $response = $middleware->process($request, $handler);
        $this->assertInstanceOf(\CakeDC\Api\Test\App\Service\v1\ArticlesService::class, $captured);
        $this->assertSame('articles', $captured->getName());
        $this->assertSame('HANDLER', (string)$response->getBody());
    }
}
