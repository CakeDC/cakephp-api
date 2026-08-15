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
     * @return array
     */
    public function execute(): array
    {
        $user = $this->getIdentity();
        $secretVerified = $user['secret_verified'] ?? null;
        // showing QR-code until shared secret is verified
        if (!$secretVerified) {
            $secret = $this->onVerifyGetSecret($user);
            if (empty($secret)) {
                throw new \Exception('Secret generation issue, please try again');
            }
            $secretDataUri = $this->getQRCodeImageAsDataUri($user['email'], $secret);

            return [
                'secretDataUri' => $secretDataUri,
                'secret' => $secret,
                'verified' => false,
            ];
        }

        return ['verified' => true];
    }

    /**
     * onVerifyGetSecret
     *
     * @param array $user User.
     * @return string
     */
    protected function onVerifyGetSecret(array $user): string
    {
        if (isset($user['secret']) && $user['secret']) {
            return $user['secret'];
        }

        $secret = $this->createSecret();
        try {
            $query = $this->getUsersTable()->updateQuery();
            $query
                ->set(['secret' => $secret])
                ->where(['id' => $user['id']]);
            $query->execute();
        } catch (\Exception) {
            $message = __d('cake_d_c/api', 'Could not verify, please try again');

            throw new \Exception($message);
        }

        return $secret;
    }
}
