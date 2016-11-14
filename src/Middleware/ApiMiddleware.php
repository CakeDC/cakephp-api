<?php

namespace CakeDC\Api\Middleware;

use Cake\Core\Configure;
use Cake\Routing\Exception\RedirectException;
use Cake\Routing\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\RedirectResponse;
use Cake\Http\RequestTransformer;
use Cake\Http\ResponseTransformer;

use CakeDC\Api\Service\ConfigReader;
use CakeDC\Api\Service\ServiceRegistry;

/**
 * Applies routing rules to the request and creates the controller
 * instance if possible.
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
		$prefix = 'api';
		$useVersioning = Configure::read('Api.useVersioning');
		if ($useVersioning) {
			$versionPrefix = Configure::read('Api.versionPrefix');
			$expr = '#/' . $prefix . '/(?<version>' . $versionPrefix . '\d+)' . '/' . '(?<service>[^/?]+)' . '(?<base>/?.*)#';
		} else {
			$expr = '#/' . $prefix . '/' . '(?<service>[^/?]+)' . '(?<base>/?.*)#';
		}
		$path = $request->getUri()->getPath();
		// print_r($expr);
		// print_r($path);
		// exit;
		if(preg_match($expr, $path, $matches)) {
			
			$cakeRequest = RequestTransformer::toCake($request);
			$cakeResponse = ResponseTransformer::toCake($response); 			
			// debug($matches);
			$version = isset($matches['version']) ? $matches['version'] : null;
			$service = $matches['service'];
			// $base = $matches['base'];
			$url = '/' . $service;
			if (!empty($matches['base'])) {
				$url .= $matches['base'];
			}
			$options = [
				'service' => $service,
				'version' => $version,
				'request' => $cakeRequest,
				'response' => $cakeResponse,
				'baseUrl' => $url,
			];
			// print_r($u);
			try {
				$options += (new ConfigReader())->serviceOptions($service, $version);
				$Service = ServiceRegistry::get($service, $options);
				$result = $Service->dispatch();

				$cakeResponse = $Service->respond($result);
			} catch (Exception $e) {
				$cakeResponse->statusCode(400);
				$cakeResponse->body($e->getMessage());
			}
			return ResponseTransformer::toPsr($cakeResponse); 
			// return $response;
		}
        // try {
            // Router::setRequestContext($request);
            // $params = (array)$request->getAttribute('params', []);
            // if (empty($params['controller'])) {
                // $path = $request->getUri()->getPath();
                // $request = $request->withAttribute('params', Router::parse($path, $request->getMethod()));
            // }
        // } catch (RedirectException $e) {
            // return new RedirectResponse(
                // $e->getMessage(),
                // $e->getCode(),
                // $response->getHeaders()
            // );
        // }
		
		// $path = $request->getUri()->getPath();
		// debug(Configure::read('Api'));
		// debug($path);

        return $next($request, $response);
    }
}
