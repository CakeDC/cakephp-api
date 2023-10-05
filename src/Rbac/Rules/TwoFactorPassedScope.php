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
namespace CakeDC\Api\Rbac\Rules;

use Authentication\Authenticator\JwtAuthenticator;
use CakeDC\Auth\Rbac\Rules\AbstractRule;
use Cake\Utility\Hash;
use Cake\Routing\Router;
use OutOfBoundsException;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Owner rule class, used to match ownership permissions
 */
class TwoFactorPassedScope extends AbstractRule
{

    protected $_defaultConfig = [
    ];

    /**
     * @inheritDoc
     */
    public function allowed($user, $role, ServerRequestInterface $request)
    {
        $authentication = $request->getAttribute('authentication');
        if ($authentication === null) {
            return false;
        }
        $provider = $authentication->getAuthenticationProvider();
        if ($provider === null || !($provider instanceof JwtAuthenticator)) {
            return false;
        }
        $payload = $provider->getPayload();
        $aud = Router::url('/', true);

        return $payload->aud == $aud;
    }
}
