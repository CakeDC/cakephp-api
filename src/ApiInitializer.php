<?php
/**
 * Copyright 2016 - 2019, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2016 - 2019, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Api;

use Authentication\AuthenticationService;
use Authorization\AuthorizationService;
use Authorization\AuthorizationServiceInterface;
use Authorization\AuthorizationServiceProviderInterface;
use Authorization\Policy\MapResolver;
use Authorization\Policy\OrmResolver;
use Authorization\Policy\ResolverCollection;
use Cake\Core\Configure;
use Cake\Http\ServerRequest;
use CakeDC\Api\Rbac\ApiRbac;
use CakeDC\Auth\Rbac\Rbac;
use CakeDC\Auth\Policy\CollectionPolicy;
use CakeDC\Auth\Policy\RbacPolicy;
use CakeDC\Auth\Policy\SuperuserPolicy;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ApiInitializer implements AuthorizationServiceProviderInterface
{

    public function getAuthenticationService()
    {
        $service = new AuthenticationService();
        $service->loadIdentifier('Authentication.JwtSubject', []);

        $service->loadIdentifier('Authentication.Password', []);
        $service->loadAuthenticator('Authentication.Session', [
            'sessionKey' => 'Auth',
        ]);

        $service->loadIdentifier('Authentication.Token', [
            'dataField' => 'token',
            'tokenField' => 'api_token',
        ]);
        $service->loadAuthenticator('Authentication.Token', [
            'queryParam' => 'token',
        ]);

        return $service;
    }

    public function getAuthorizationService(ServerRequestInterface $request, ResponseInterface $response)
    {
        $map = new MapResolver();
        $rbac = new ApiRbac();
        $map->map(
            ServerRequest::class,
            new CollectionPolicy([
                //SuperuserPolicy::class,
                new RbacPolicy([
                    'adapter' => $rbac
                ])
            ])
        );

        $orm = new OrmResolver();

        $resolver = new ResolverCollection([
            $map,
            $orm
        ]);

        return new AuthorizationService($resolver);
    }

}
