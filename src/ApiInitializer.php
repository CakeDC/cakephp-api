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

namespace CakeDC\Api;

use Authentication\AuthenticationService;
use Authorization\AuthorizationService;
use Authorization\AuthorizationServiceInterface as ASI;
use Authorization\AuthorizationServiceProviderInterface;
use Authorization\Policy\MapResolver;
use Authorization\Policy\OrmResolver;
use Authorization\Policy\ResolverCollection;
use Cake\Core\Configure;
use Cake\Http\ServerRequest;
use CakeDC\Api\Rbac\ApiRbac;
use CakeDC\Auth\Policy\CollectionPolicy;
use CakeDC\Auth\Policy\RbacPolicy;
use Psr\Http\Message\ServerRequestInterface;

class ApiInitializer implements AuthorizationServiceProviderInterface
{
    /**
     * @return \Authentication\AuthenticationService
     */
    public function getAuthenticationService(): AuthenticationService
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

        $service->loadAuthenticator('Authentication.Jwt', [
            'header' => 'Authorization',
            'queryParam' => 'token',
            'tokenPrefix' => 'bearer',
            'algorithms' => ['HS256', 'HS512'],
            'returnPayload' => false,
            'secretKey' => Configure::read('Api.Jwt.AccessToken.secret'),
        ]);

        return $service;
    }

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request Request instance.
     * @return \Authorization\AuthorizationServiceInterface
     */
    public function getAuthorizationService(ServerRequestInterface $request): ASI
    {
        $map = new MapResolver();
        $rbac = new ApiRbac();
        $map->map(
            ServerRequest::class,
            new CollectionPolicy([
                //SuperuserPolicy::class,
                new RbacPolicy([
                    'adapter' => $rbac,
                ]),
            ])
        );

        $orm = new OrmResolver();

        $resolver = new ResolverCollection([
            $map,
            $orm,
        ]);

        return new AuthorizationService($resolver);
    }
}
