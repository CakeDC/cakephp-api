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
class OtpVerifyCheckAction extends OtpVerifyAction
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
        $codeVerified = false;
        $verificationCode = $this->getData('code');
        $user = $this->getIdentity();
        $entity = $this->getUsersTable()->get($user['id']);

        if (!empty($entity['secret'])) {
            $codeVerified = $this->verifyCode($entity['secret'], $verificationCode);
        }

        if (!$codeVerified) {
            throw new \Exception(__d('cake_d_c/api', 'Verification code is invalid. Try again'));
        }

        unset($user['secret']);

        if (!$user['secret_verified']) {
            $this->getUsersTable()->query()->update()
                ->set(['secret_verified' => true])
                ->where(['id' => $user['id']])
                ->execute();
        }

        return $this->generateTokenResponse($user->toArray(), '2fa');
    }

}
