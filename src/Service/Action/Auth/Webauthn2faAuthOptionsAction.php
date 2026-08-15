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
use CakeDC\Api\Webauthn\AuthenticateAdapter;
use CakeDC\Users\Controller\Traits\CustomUsersTableTrait;

/**
 * Class LoginAction
 *
 * @package CakeDC\Api\Service\Action
 */
class Webauthn2faAuthOptionsAction extends Action
{
    use CustomUsersTableTrait;

    /**
     * Execute action.
     *
     * @return \Webauthn\PublicKeyCredentialRequestOptions
     */
    public function execute(): \Webauthn\PublicKeyCredentialRequestOptions
    {
        $request = $this->getService()->getRequest();
        $adapter = new AuthenticateAdapter($request, $this->getUsersTable(), $this->getIdentity());

        return $adapter->getOptions();
    }
}
