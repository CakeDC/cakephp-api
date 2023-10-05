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
use Cake\Core\Configure;
use Cake\Http\Exception\BadRequestException;

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
     * @return mixed
     */
    public function execute()
    {
        $adapter = new AuthenticateAdapter($this->getService()->getRequest(), $this->getUsersTable(), $this->getIdentity());

        return $adapter->getOptions();
   }
}
