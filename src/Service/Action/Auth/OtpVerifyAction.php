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
use RobThree\Auth\TwoFactorAuth;

/**
 * Class LoginAction
 *
 * @package CakeDC\Api\Service\Action
 */
abstract class OtpVerifyAction extends Action
{
    use CustomUsersTableTrait;

    /**
     * @var \RobThree\Auth\TwoFactorAuth $tfa
     */
    public $tfa;

    public function initialize(array $config): void
    {
        $this->tfa = new TwoFactorAuth(
            Configure::read('OneTimePasswordAuthenticator.issuer'),
            Configure::read('OneTimePasswordAuthenticator.digits'),
            Configure::read('OneTimePasswordAuthenticator.period'),
            Configure::read('OneTimePasswordAuthenticator.algorithm'),
            Configure::read('OneTimePasswordAuthenticator.qrcodeprovider'),
            Configure::read('OneTimePasswordAuthenticator.rngprovider')
        );
    }

    /**
     * createSecret
     *
     * @return string base32 shared secret stored in users table
     */
    public function createSecret()
    {
        return $this->tfa->createSecret();
    }

    /**
     * verifyCode
     * Verifying tfa code with shared secret
     *
     * @param string $secret of the user
     * @param string $code from verification form
     * @return bool
     */
    public function verifyCode($secret, $code)
    {
        return $this->tfa->verifyCode($secret, $code);
    }

    /**
     * getQRCodeImageAsDataUri
     *
     * @param string $issuer issuer
     * @param string $secret secret
     * @return string base64 string containing QR code for shared secret
     */
    public function getQRCodeImageAsDataUri($issuer, $secret)
    {
        return $this->tfa->getQRCodeImageAsDataUri($issuer, $secret);
    }

}
