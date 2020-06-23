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

use CakeDC\Api\Service\Action\Auth\LoginAction as Action;

/**
 * Class LoginAction
 *
 * @package CakeDC\Api\Service\Action
 */
class JwtLoginAction extends Action
{
    use JwtTokenTrait;

    /**
     * Update remember me and determine redirect url after user identified
     *
     * @param array $user user data after identified
     * @param bool $socialLogin is social login
     * @return array|bool
     */
    protected function _afterIdentifyUser($user, $socialLogin = false)
    {
        $user = parent::_afterIdentifyUser($user, $socialLogin);

        if (empty($user)) {
            return false;
        }

        return $this->generateTokenResponse($user);
    }
}
