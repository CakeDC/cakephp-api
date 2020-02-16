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

use Cake\Core\Configure;
use Cake\Http\CallbackStream;
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
 * @deprecated use ParseApiRequestMiddleware and ProcessApiRequestMiddleware instead
 */
class ApiMiddleware implements MiddlewareInterface
{
    /**
     * Process an incoming server request.
     *
     * Processes an incoming server request in order to produce a response.
     * If unable to produce the response itself, it may delegate to the provided
     * request handler to do so.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @param \Psr\Http\Server\RequestHandlerInterface $handler The request handler.
     *
     * @return \Psr\Http\Message\ResponseInterface A response.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $prefix = Configure::read('Api.prefix');
        if (empty($prefix)) {
            $prefix = 'api';
        }
        $useVersioning = Configure::read('Api.useVersioning');
        if ($useVersioning) {
            $versionPrefix = Configure::read('Api.versionPrefix');
            $expr = '#/' . $prefix . '/(?<version>' . $versionPrefix . '\d+)' . '/' .
                '(?<service>[^/?]+)' . '(?<base>/?.*)#';
        } else {
            $expr = '#/' . $prefix . '/' . '(?<service>[^/?]+)' . '(?<base>/?.*)#';
        }

        $path = $request->getUri()->getPath();
        if (preg_match($expr, $path, $matches)) {
            $version = $matches['version'] ?? null;
            $service = $matches['service'];

            $url = '/' . $service;
            if (!empty($matches['base'])) {
                $url .= $matches['base'];
            }
            $options = [
                'service' => $service,
                'version' => $version,
                'request' => $request,
                'baseUrl' => $url,
            ];

            try {
                $options += (new ConfigReader())->serviceOptions($service, $version);
                $Service = ServiceRegistry::getServiceLocator()->get($service, $options);
                $result = $Service->dispatch();

                $response = $Service->respond($result);
            } catch (Exception $e) {
                $response = new Response();
                $response->withStatus(400);
                $response = $response->withBody(new CallbackStream(function () use ($e) {
                    echo $e->getMessage();
                }));
            }

            return $response;
        }

        return $handler->handle($request);
    }
}
