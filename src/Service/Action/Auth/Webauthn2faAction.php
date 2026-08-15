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
use CakeDC\Api\Webauthn\RegisterAdapter;
use CakeDC\Users\Controller\Traits\CustomUsersTableTrait;

/**
 * Class LoginAction
 *
 * @package CakeDC\Api\Service\Action
 */
class Webauthn2faAction extends Action
{
    use CustomUsersTableTrait;

    /**
     * Execute action.
     *
     * @return array
     */
    public function execute(): array
    {
        $user = $this->getIdentity();
        $request = $this->getService()->getRequest();
        $adapter = new RegisterAdapter($request, $this->getUsersTable(), $user);

        return [
            'isRegister' => !$adapter->hasCredential(),
            'username' => $user->webauthn_username ?? $user->username,
        ];
    }
}
