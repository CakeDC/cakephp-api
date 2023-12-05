<?php
declare(strict_types=1);

/**
 * Copyright 2010 - 2019, Cake Development Corporation (https://www.cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2010 - 2019, Cake Development Corporation (https://www.cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Api\Traits;

use Authorization\AuthorizationServiceInterface;
use Cake\Http\ServerRequest;
use Cake\Routing\Router;
use Laminas\Diactoros\Uri;

use Authentication\Authenticator\UnauthenticatedException;
use Cake\Core\Configure;
use Cake\Http\Response;
use CakeDC\Api\Service\ConfigReader;
use CakeDC\Api\Service\ServiceRegistry;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

trait IsAuthorizedTrait
{
    /**
     * Returns true if the target url is authorized for the logged in user
     *
     * @param string|array|null $url url that the user is making request.
     * @param string $action Authorization action.
     * @return bool
     */
    public function isAuthorized($url = null, $action = 'access')
    {
        if (empty($url)) {
            return false;
        }

        if (is_array($url)) {
            return $this->_checkCanAccess(Router::normalize(Router::reverse($url)), $action);
        }

        return $this->_checkCanAccess($url, $action);
    }

    /**
     * Check if user can acces url
     *
     * @param string $url to check permissions
     * @param string $action Authorization action.
     * @return bool
     */
    protected function _checkCanAccess($url, $action)
    {
        /**
         * @var \Cake\Http\ServerRequest $request
         */
        $request = $this->getService()->getRequest();
        $service = $request->getAttribute('authorization');
        if (!$service instanceof AuthorizationServiceInterface) {
            throw new \RuntimeException(__('Could not find the authorization service in the request.'));
        }
        $identity = $request->getAttribute('identity');
        $targetRequest = $this->_createUrlRequestToCheck($url);
        if (!$targetRequest) {
            return false;
        }

        return $service->can($identity, $action, $targetRequest);
    }

    /**
     * Create the url request to check authorization
     *
     * @param string $url The target url.
     * @return \Cake\Http\ServerRequest
     */
    protected function _createUrlRequestToCheck($url)
    {
        $uri = new Uri($url);
        $targetRequest = new ServerRequest([
            'uri' => $uri,
        ]);
        $params = Router::parseRequest($targetRequest);
        $targetRequest = $targetRequest->withAttribute('params', $params);

        $service = $this->_getRequestWithService($url, $targetRequest);
        if ($service == null) {
            return false;
        }

        return $targetRequest->withAttribute(
            'rbac',
            $this->getService()->getRequest()->getAttribute('rbac')
        )->withAttribute(
            'service',
            $service
        )->withAttribute(
            'identity',
            $this->getService()->getRequest()->getAttribute('identity')
        )->withAttribute(
            'authentication',
            $this->getService()->getRequest()->getAttribute('authentication')
        )->withAttribute(
            'authenticationResult',
            $this->getService()->getRequest()->getAttribute('authenticationResult')
        );
    }

    /**
     * Returns service by the url
     *
     * @param string $url The target url.
     * @return \Cake\Http\Response|\Psr\Http\Message\ResponseInterface|null
     */
    public function _getRequestWithService($url, ServerRequestInterface $request)
    {
        $response = null;
        $service = null;
        $expr = '#/' . '(?<service>[^/?]+)' . '(?<base>/?.*)#';
        $altExpr = null;
        $useVersioning = Configure::read('Api.useVersioning');
        if ($useVersioning) {
            $versionPrefix = Configure::read('Api.versionPrefix');
            $expr = '#/(?<version>' . $versionPrefix . '\d+)' . '/' .
                '(?<service>[^/?]+)' . '(?<base>/?.*)#';
            $altExpr = '#/' . $prefix . '/' . '(?<service>[^/?]+)' . '(?<base>/?.*)#';
        }

        $path = $request->getUri()->getPath();
        if (preg_match($expr, $path, $matches)) {
            return $this->_matchRequest($request, $matches);
        }
        if ($altExpr !== null && preg_match($altExpr, $path, $matches)) {
            return $this->_matchRequest($request, $matches);
        }

        return null;
    }

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request Request object.
     * @param \Psr\Http\Server\RequestHandlerInterface $handler Request handler.
     * @param mixed $matches Matches definition.
     * @return \Cake\Http\Response|\Psr\Http\Message\ResponseInterface|null
     */
    protected function _matchRequest(ServerRequestInterface $request, $matches)
    {
        $service = null;
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
        ];

        try {
            ServiceRegistry::getServiceLocator()->clear();
            $options += (new ConfigReader())->serviceOptions($serviceName, $version);
            $service = ServiceRegistry::getServiceLocator()->get($serviceName, $options);
            $result = $service->dispatchPrepareAction();

            return $service;
        } catch (UnauthenticatedException $e) {
            return false;
        } catch (Exception $e) {
            return false;
        }
        if ($response === null) {
            return false;
        }

        return $response;
    }

}
