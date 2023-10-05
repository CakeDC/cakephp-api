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
use CakeDC\Api\Webauthn\RegisterAdapter;

/**
 * Class LoginAction
 *
 * @package CakeDC\Api\Service\Action
 */
class Webauthn2faRegisterAction extends Action
{
    use CustomUsersTableTrait;

    /**
     * Execute action.
     *
     * @return mixed
     */
    public function execute()
    {
        $user = $this->getIdentity();
        $adapter = new RegisterAdapter($this->getService()->getRequest(), $this->getUsersTable(), $user);

        if (!$adapter->hasCredential()) {
            $adapter->verifyResponse();

            return ['success' => true];
        }

        throw new BadRequestException(
            __d('cake_d_c/api', 'User already has configured webauthn2fa')
        );

   }
}
