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

namespace CakeDC\Api\Middleware;

use Authentication\Authenticator\UnauthenticatedException;
use Cake\Core\Configure;
use Cake\Core\ContainerInterface;
use Cake\Http\Response;
use CakeDC\Api\Service\ConfigReader;
use CakeDC\Api\Service\ServiceRegistry;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Applies routing rules to the request and creates the controller
 * instance if possible.
 */
class ParseApiRequestMiddleware implements MiddlewareInterface
{
    /**
     * Container
     *
     * @var \Cake\Core\ContainerInterface|null
     */
    protected ?ContainerInterface $container;

    /**
     * Constructor.
     *
     * @param \Cake\Core\ContainerInterface|null $container Application DI container.
     */
    public function __construct(?ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * Process an incoming server request.
     *
     * Processes an incoming server request in order to produce a response.
     * If unable to produce the response itself, it may delegate to the provided
     * request handler to do so.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @param \Psr\Http\Server\RequestHandlerInterface $handler The request handler.
     * @return \Psr\Http\Message\ResponseInterface A response.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->container === null) {
            $this->container = $request->getAttribute('container');
        }
        $response = null;
        $service = null;
        $prefix = Configure::read('Api.prefix');
        if (empty($prefix)) {
            $prefix = 'api';
        }
        $expr = '#/' . $prefix . '/' . '(?<service>[^/?]+)' . '(?<base>/?.*)#';
        $altExpr = null;
        $useVersioning = Configure::read('Api.useVersioning');
        if ($useVersioning) {
            $versionPrefix = Configure::read('Api.versionPrefix');
            $expr = '#/' . $prefix . '/(?<version>' . $versionPrefix . '\d+)' . '/' .
                '(?<service>[^/?]+)' . '(?<base>/?.*)#';
            $altExpr = '#/' . $prefix . '/' . '(?<service>[^/?]+)' . '(?<base>/?.*)#';
        }

        $path = $request->getUri()->getPath();
        if (preg_match($expr, $path, $matches)) {
            return $this->_matchRequest($request, $handler, $matches);
        }
        if ($altExpr !== null && preg_match($altExpr, $path, $matches)) {
            return $this->_matchRequest($request, $handler, $matches);
        }

        return $handler->handle($request);
    }

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request Request object.
     * @param \Psr\Http\Server\RequestHandlerInterface $handler Request handler.
     * @param mixed $matches Matches definition.
     * @return \Cake\Http\Response|\Psr\Http\Message\ResponseInterface|null
     */
    protected function _matchRequest(ServerRequestInterface $request, RequestHandlerInterface $handler, $matches)
    {
        $service = null;
        $response = null;
        $version = $matches['version'] ?? null;
        $serviceName = $matches['service'];

        $url = '/' . $serviceName;
        if (!empty($matches['base'])) {
            $url .= $matches['base'];
        }
        $options = [
            'service' => $serviceName,
            'version' => $version,
            'request' => $request,
            'baseUrl' => $url,
            'container' => $this->container,
        ];

        try {
            $options += (new ConfigReader())->serviceOptions($serviceName, $version);
            $service = ServiceRegistry::getServiceLocator()
                                      ->get($serviceName, $options);
            $result = $service->dispatchPrepareAction();

            if ($result !== null) {
                $response = $service->respond($result);
            } else {
                $request = $request->withAttribute('service', $service);

                return $handler->handle($request);
            }
        } catch (UnauthenticatedException $e) {
            if ($service !== null) {
                $service->getResult()
                        ->setCode(401);
                $service->getResult()
                        ->setException($e);
                $response = $service->respond();
            }
        } catch (Exception $e) {
            if ($service !== null) {
                $service->getResult()
                        ->setCode(400);
                $service->getResult()
                        ->setException($e);
                $response = $service->respond();
            }
        }
        if ($response === null) {
            $response = (new Response())->withStatus(400);
        }

        return $response;
    }

    /**
     * Sets the service parser.
     *
     * @param \Cake\Core\ContainerInterface $container A ContainerInterface instance.
     * @return void
     */
    public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;
    }
}
