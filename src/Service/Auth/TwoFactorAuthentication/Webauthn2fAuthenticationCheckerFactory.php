<?php
declare(strict_types=1);

/**
 * Copyright 2010 - 2019, Cake Development Corporation (https://www.cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2010 - 2018, Cake Development Corporation (https://www.cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace CakeDC\Api\Service\Auth\TwoFactorAuthentication;

use Cake\Core\Configure;

/**
 * Factory for two authentication checker
 *
 * @package CakeDC\Auth\Auth
 */
class Webauthn2fAuthenticationCheckerFactory
{
    /**
     * Get the two factor authentication checker
     *
     * @return \CakeDC\Api\Service\Auth\TwoFactorAuthentication\Webauthn2fAuthenticationCheckerInterface
     */
    public function build(): Webauthn2fAuthenticationCheckerInterface
    {
        $className = Configure::read('Api.Webauthn2fa.checker');
        $interfaces = class_implements($className);
        $required = Webauthn2fAuthenticationCheckerInterface::class;

        if (in_array($required, $interfaces)) {
            return new $className();
        }
        $message = "Invalid config for 'Webauthn2fa.checker', " .
         sprintf("'%s' does not implement '%s'", $className, $required);
        throw new \InvalidArgumentException($message);
    }
}
