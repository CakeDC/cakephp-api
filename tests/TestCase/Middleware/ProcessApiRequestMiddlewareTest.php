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

use Cake\Http\Response;
use Cake\Http\ServerRequest;
use CakeDC\Api\Middleware\ProcessApiRequestMiddleware;
use CakeDC\Api\Service\ServiceRegistry;
use CakeDC\Api\Test\ConfigTrait;
use CakeDC\Api\TestSuite\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

/**
 * Unit tests for the API request processing middleware.
 */
class ProcessApiRequestMiddlewareTest extends TestCase
{
    use ConfigTrait;

    protected function tearDown(): void
    {
        ServiceRegistry::getServiceLocator()->clear();
        parent::tearDown();
    }

    protected function buildServiceRequest(): ServerRequest
    {
        ServiceRegistry::getServiceLocator()->clear();
        $this->initializeRequest([
            'params' => ['service' => 'articles'],
        ], 'GET');
        $service = ServiceRegistry::getServiceLocator()->get('articles', [
            'version' => null,
            'service' => 'articles',
            'request' => $this->testRequest,
            'response' => $this->testResponse,
            'baseUrl' => '/articles',
        ]);
        $service->dispatchPrepareAction();

        return $this->testRequest->withAttribute('service', $service);
    }

    public function testNoServiceDelegatesToHandler(): void
    {
        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $response = new Response();
                $response = $response->withStringBody('HANDLER');

                return $response;
            }
        };
        $middleware = new ProcessApiRequestMiddleware();
        $request = new ServerRequest(['url' => '/articles']);
        $response = $middleware->process($request, $handler);
        $this->assertSame('HANDLER', (string)$response->getBody());
    }

    public function testWithServiceProcessesAction(): void
    {
        $request = $this->buildServiceRequest();
        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                throw new RuntimeException('handler should not be called');
            }
        };
        $middleware = new ProcessApiRequestMiddleware();
        $response = $middleware->process($request, $handler);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testServiceDispatchExceptionReturns400(): void
    {
        $request = $this->buildServiceRequest();
        $service = $request->getAttribute('service');
        $service->getEventManager()->on('Service.afterDispatch', function (): void {
            throw new RuntimeException('after-dispatch boom');
        });
        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                throw new RuntimeException('handler should not be called');
            }
        };
        $middleware = new ProcessApiRequestMiddleware();
        $response = $middleware->process($request, $handler);
        $this->assertInstanceOf(Response::class, $response);
        $decoded = json_decode((string)$response->getBody(), true);
        $this->assertSame('error', $decoded['status']);
        $this->assertSame(400, $decoded['code']);
    }
}
