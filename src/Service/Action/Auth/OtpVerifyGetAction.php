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
class OtpVerifyGetAction extends OtpVerifyAction
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
        $secretVerified = $user['secret_verified'] ?? null;
        // showing QR-code until shared secret is verified
        if (!$secretVerified) {
            $secret = $this->onVerifyGetSecret($user);
            if (empty($secret)) {
                throw new \Exception('Secret generation issue, please try again');
            } else {
                $secretDataUri = $this->getQRCodeImageAsDataUri($user['email'], $secret);
                $result = ['secretDataUri' => $secretDataUri, 'verified' => false];
            }
        } else {
            $result = ['verified' => true];
        }

        return $result;
    }

    protected function onVerifyGetSecret($user)
    {
        if (isset($user['secret']) && $user['secret']) {
            return $user['secret'];
        }

        $secret = $this->createSecret();
        try {
            $query = $this->getUsersTable()->query();
            $query->update()
                ->set(['secret' => $secret])
                ->where(['id' => $user['id']]);
            $query->execute();
        } catch (\Exception $e) {
            $message = __d('cake_d_c/api', 'Could not verify, please try again');

            throw new \Exception($message);
        }

        return $secret;
    }
}
