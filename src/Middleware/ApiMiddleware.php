<?php
/**
 * Copyright 2016 - 2018, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2016 - 2018, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Api\Middleware;

use CakeDC\Api\Service\ConfigReader;
use CakeDC\Api\Service\ServiceRegistry;
use Cake\Core\Configure;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Applies routing rules to the request and creates the controller
 * instance if possible.
 * @deprecated use ParseApiRequestMiddleware and ProcessApiRequestMiddleware instead
 */
class ApiMiddleware
{

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @param \Psr\Http\Message\ResponseInterface $response The response.
     * @param callable $next The next middleware to call.
     * @return \Psr\Http\Message\ResponseInterface A response.
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $next)
    {
        $prefix = Configure::read('Api.prefix');
        if (empty($prefix)) {
            $prefix = 'api';
        }
        $useVersioning = Configure::read('Api.useVersioning');
        if ($useVersioning) {
            $versionPrefix = Configure::read('Api.versionPrefix');
            $expr = '#/' . $prefix . '/(?<version>' . $versionPrefix . '\d+)' . '/' . '(?<service>[^/?]+)' . '(?<base>/?.*)#';
        } else {
            $expr = '#/' . $prefix . '/' . '(?<service>[^/?]+)' . '(?<base>/?.*)#';
        }

        $path = $request->getUri()->getPath();
        if (preg_match($expr, $path, $matches)) {
            $version = isset($matches['version']) ? $matches['version'] : null;
            $service = $matches['service'];

            $url = '/' . $service;
            if (!empty($matches['base'])) {
                $url .= $matches['base'];
            }
            $options = [
                'service' => $service,
                'version' => $version,
                'request' => $request,
                'response' => $response,
                'baseUrl' => $url,
            ];

            try {
                $options += (new ConfigReader())->serviceOptions($service, $version);
                $Service = ServiceRegistry::getServiceLocator()->get($service, $options);
                $result = $Service->dispatch();

                $response = $Service->respond($result);
            } catch (Exception $e) {
                $response->withStatus(400);
                $response = $response->withStringBody($e->getMessage());
            }

            return $response;
        }

        return $next($request, $response);
    }
}
