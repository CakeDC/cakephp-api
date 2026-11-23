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
use Cake\Core\Configure;
use Cake\Http\Exception\BadRequestException;

/**
 * Class LoginAction
 *
 * @package CakeDC\Api\Service\Action
 */
class Webauthn2faRegisterOptionsAction extends Action
{
    use CustomUsersTableTrait;
    use SecondFactorTrait;

    /**
     * Execute action.
     *
     * @return mixed
     */
    public function execute()
    {
        $user = $this->getIdentity();
        $this->_refuseEnrollmentIf($this->_hasVerifiedOtp($user));
        $adapter = new RegisterAdapter($this->getService()->getRequest(), $this->getUsersTable(), $user);

        // a key registered under another domain counts too: the domain can come from the Referer
        if (!$adapter->hasCredentialOnAnyDomain()) {
            return $adapter->getOptions();
        }
        throw new BadRequestException(
            __d('cake_d_c/api', 'User already has configured webauthn2fa')
        );

   }
}
