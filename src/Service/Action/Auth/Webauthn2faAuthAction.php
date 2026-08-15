<?php
declare(strict_types=1);

/**
 * Copyright 2018 - 2020, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2018 - 2020, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Api\Service\Action\Auth;

use Cake\Log\Log;
use CakeDC\Api\Service\Action\Action;
use CakeDC\Api\Webauthn\AuthenticateAdapter;
use CakeDC\Users\Controller\Traits\CustomUsersTableTrait;

/**
 * Class LoginAction
 *
 * @package CakeDC\Api\Service\Action
 */
class Webauthn2faAuthAction extends Action
{
    use CustomUsersTableTrait;
    use JwtTokenTrait;

    /**
     * Execute action.
     *
     * @return array
     * @throws \Throwable
     */
    public function execute(): array
    {
        try {
            $user = $this->getIdentity();
            $adapter = new AuthenticateAdapter($this->getService()->getRequest(), $this->getUsersTable(), $user);
            $adapter->verifyResponse();
            $adapter->deleteStore();

            return $this->generateTokenResponse($user->toArray(), '2fa');
        } catch (\Throwable $throwable) {
            $user = $this->getIdentity();
            $message = __d('cake_d_c/api', 'Register error with webauthn for user id: {0}', $user['id'] ?? 'empty');
            Log::debug($message);
            throw $throwable;
        }
    }
}
