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

use CakeDC\Api\Service\Action\Auth\SocialLoginAction as Action;

/**
 * Class SocialLoginAction
 *
 * @package CakeDC\Api\Service\Action
 */
class JwtSocialLoginAction extends Action
{
    use JwtTokenTrait;

    /**
     * Execute action.
     *
     * @return mixed
     * @throws \CakeDC\Api\Service\Action\Exception
     */
    public function execute()
    {
        $user = parent::execute();

        if (empty($user)) {
            return false;
        }

        return $this->generateTokenResponse($user);
    }
}
