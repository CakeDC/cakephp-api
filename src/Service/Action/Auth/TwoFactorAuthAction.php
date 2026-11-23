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

/**
 * Class LoginAction
 *
 * @package CakeDC\Api\Service\Action
 */
class TwoFactorAuthAction extends Action
{
    use CustomUsersTableTrait;
    use JwtTokenTrait;
    use SecondFactorTrait;

    /**
     * Execute action.
     *
     * @return array
     */
    public function execute(): array
    {
        $user = $this->getIdentity();
        // keys of every domain count, and a verified secret counts as OTP
        $hasWebauthn = $this->hasWebauthnCredential($user);
        $hasOtp = $this->hasVerifiedOtp($user);
        $user = $user->toArray();

        return [
            'hasRegistered2fa' => $hasWebauthn && $hasOtp,
            'hasOtp' => $hasOtp,
            'hasWebauthn' => $hasWebauthn,
            'enabledWebauthn' => $this->isEnabledWebauthn2faAuthentication((array)$user),
            'enabledOtp' => $this->isEnabledOneTimePasswordAuthentication((array)$user),
        ];
    }
}
