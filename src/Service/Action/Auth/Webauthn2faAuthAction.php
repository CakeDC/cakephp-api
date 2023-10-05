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

use CakeDC\Api\Service\Action\Action;
use CakeDC\Users\Controller\Traits\CustomUsersTableTrait;
use Cake\Core\Configure;
use CakeDC\Api\Webauthn\AuthenticateAdapter;

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
     * @return mixed
     */
    public function execute()
    {
        try {
            $user = $this->getIdentity();
            $adapter = new AuthenticateAdapter($this->getService()->getRequest(), $this->getUsersTable(), $user);
            $adapter->verifyResponse();
            $adapter->deleteStore();

            return $this->generateTokenResponse($user->toArray(), '2fa');
        } catch (\Throwable $e) {
            $user = $this->getIdentity();
            \Cake\Log\Log::debug(__d('cake_d_c/api', 'Register error with webauthn for user id: {0}', $user['id'] ?? 'empty'));
            throw $e;
        }
   }
}
