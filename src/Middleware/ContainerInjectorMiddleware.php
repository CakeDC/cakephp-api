<?php
declare(strict_types=1);

namespace CakeDC\Api\Middleware;

use Cake\Core\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Container Injector Middleware
 */
class ContainerInjectorMiddleware implements MiddlewareInterface
{
    /**
     * @var \Cake\Core\ContainerInterface
     */
    protected $container;

    /**
     * Constructor
     *
     * @param \Cake\Core\ContainerInterface $container The container to build controllers with.
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Serve assets if the path matches one.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @param \Psr\Http\Server\RequestHandlerInterface $handler The request handler.
     * @return \Psr\Http\Message\ResponseInterface A response.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $handler->handle($request->withAttribute('container', $this->container));
    }
}
