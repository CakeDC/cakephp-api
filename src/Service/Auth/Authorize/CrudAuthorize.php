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

namespace CakeDC\Api\Service\Auth\Authorize;

use Cake\Core\Configure;
use Cake\Http\ServerRequest;
use CakeDC\Api\Service\Action\Action;
use CakeDC\Api\Service\Service;

/**
 * Class CrudAuthorize
 *
 * Configuration for Crud Auth is defined as next Configure structure:
 * with Api.Auth.Crud prefix
 * It could one of next types given in order of priorities:
 * - service action permission
 *   ['ServiceName' => ['actionName' => permission, ...], ...],
 * - action level global permission
 *   ['actionName' => permission, ...],
 * - service level permission - define access to service in common
 *   ['Services' => ['ServiceName' => permission, ...]]
 *
 * Additionally one can define default permission as Api.Auth.Crud.default.
 *
 * Permission defined as next rule:
 *   permission ::= <allow> | <deny> | <auth>
 *
 * @package
 * @package CakeDC\Api\Service\Auth\Authorize
 */
class CrudAuthorize extends BaseAuthorize
{
    /**
     * Checks user authorization.
     *
     * @param array $user Active user data.
     * @param \Cake\Http\ServerRequest $request Request instance.
     * @return bool
     */
    public function authorize($user, ServerRequest $request)
    {
        return $this->_actionAuth($this->_action);
    }

    /**
     * Authorize.
     *
     * @param \CakeDC\Api\Service\Action\Action $action An Action instance.
     * @return bool
     */
    protected function _actionAuth(Action $action): bool
    {
        $actionName = $action->getName();
        $serviceName = $action->getService()->getName();
        $service = $action->getService();

        $serviceActionAuth = $this->_permission($service, $serviceName . '.' . $actionName);
        if ($serviceActionAuth !== null) {
            return $serviceActionAuth === 'allow' || $serviceActionAuth == 'auth' && !empty($action->Auth->user());
        }

        $serviceAuth = $this->_permission($service, 'Service.' . $serviceName);

        $actionAuth = $this->_permission($service, $actionName);
        if ($actionAuth !== null) {
            $serviceAllow = fn($s) => $s === null || is_string($s) && $s === 'allow';
            $serviceAuthenticated = fn($s) => $s === null || is_string($s) && in_array($s, ['auth', 'allow']);
            $allow = $actionAuth === 'allow' && $serviceAllow($serviceAuth);
            $user = $action->Auth->user();
            $authenticated = $actionAuth === 'auth' && $serviceAuthenticated($serviceAuth) && !empty($user);

            return $allow || $authenticated;
        }

        return $this->_serviceAuth($action->getService(), $action);
    }

    /**
     * Authorize service.
     *
     * @param \CakeDC\Api\Service\Service $service A Service instance.
     * @param \CakeDC\Api\Service\Action\Action $action An Action instance.
     * @return bool
     */
    protected function _serviceAuth(Service $service, Action $action): bool
    {
        $serviceName = $service->getName();
        $serviceAuth = $this->_permission($service, 'Service.' . $serviceName);
        if ($serviceAuth === null) {
            $serviceAuth = $this->_permission($service, 'default');
        }
        if ($serviceAuth !== null && is_string($serviceAuth)) {
            return $serviceAuth === 'allow' || $serviceAuth == 'auth' && !empty($action->Auth->user());
        }

        return false;
    }

    /**
     * Check permission.
     *
     * @param \CakeDC\Api\Service\Service $service A Service instance.
     * @param string $key permission key.
     * @return string|null
     */
    protected function _permission(Service $service, $key): ?string
    {
        $prefix = 'Api.Auth.Crud.';
        $useVersioning = Configure::read('Api.useVersioning');
        $versionPrefix = Configure::read('Api.versionPrefix');
        $version = $service->getVersion();
        if ($useVersioning) {
            $permission = Configure::read($prefix . $versionPrefix . $version . '.' . $key);
            if (!empty($permission)) {
                return $permission;
            }
        }
        $permission = Configure::read($prefix . $key);
        if (empty($permission)) {
            return null;
        }

        return $permission;
    }
}
