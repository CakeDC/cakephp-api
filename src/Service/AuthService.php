<?php
declare(strict_types=1);

/**
 * Copyright 2016 - 2019, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2016 - 2019, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Api\Service;

use Cake\Utility\Hash;
use CakeDC\Api\Service\Action\Auth\JwtLoginAction;
use CakeDC\Api\Service\Action\Auth\JwtRefreshAction;
use CakeDC\Api\Service\Action\Auth\JwtSocialLoginAction;
use CakeDC\Api\Service\Action\Auth\LoginAction;
use CakeDC\Api\Service\Action\Auth\RegisterAction;
use CakeDC\Api\Service\Action\Auth\ResetPasswordAction;
use CakeDC\Api\Service\Action\Auth\ResetPasswordRequestAction;
use CakeDC\Api\Service\Action\Auth\SocialLoginAction;
use CakeDC\Api\Service\Action\Auth\ValidateAccountAction;
use CakeDC\Api\Service\Action\Auth\ValidateAccountRequestAction;

/**
 * Class AuthService
 *
 * @package CakeDC\Api\Service
 */
class AuthService extends Service
{
    /**
     * @inheritdoc
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();
        $methods = ['method' => ['POST'], 'mapCors' => true];
        $this->mapAction('jwt_login', JwtLoginAction::class, $methods);
        $this->mapAction('jwt_refresh', JwtRefreshAction::class, $methods);
        $this->mapAction('jwt_social_login', JwtSocialLoginAction::class, $methods);
        $this->mapAction('login', LoginAction::class, $methods);
        $this->mapAction('register', RegisterAction::class, $methods);
        $this->mapAction('reset_password_request', ResetPasswordRequestAction::class, $methods);
        $this->mapAction('reset_password', ResetPasswordAction::class, $methods);
        $this->mapAction('validate_account_request', ValidateAccountRequestAction::class, $methods);
        $this->mapAction('validate_account', ValidateAccountAction::class, $methods);
        $this->mapAction('social_login', SocialLoginAction::class, $methods);
    }

    /**
     * Action constructor options.
     *
     * @param array $route Action route.
     * @return array
     */
    protected function _actionOptions(array $route): array
    {
        $options['Extension'] = ['CakeDC/Api.Auth/UserFormatting'];

        return Hash::merge(parent::_actionOptions($route), $options);
    }
}
