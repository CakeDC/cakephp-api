<?php
declare(strict_types=1);

/**
 * Copyright 2016 - 2026, Cake Development Corporation (https://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2016 - 2026, Cake Development Corporation (https://cakedc.com)
 * @license MIT License (https://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Api\Service\Action\Auth;

use Cake\Datasource\EntityInterface;
use Cake\Http\Exception\BadRequestException;
use CakeDC\Api\Webauthn\Repository\UserCredentialSourceRepository;
use Webauthn\PublicKeyCredentialSource;

/**
 * Checks which second factors an identity has registered.
 *
 * A password login gives a `/2fa` token, and that token can enroll a factor. Once one factor is
 * registered, the token enrolls no other one.
 *
 * Expects the using class to provide `getService()` and `getUsersTable()`.
 */
trait SecondFactorTrait
{
    /**
     * Whether the identity has a verified OTP secret.
     *
     * @param \Cake\Datasource\EntityInterface|array|null $identity Identity.
     * @return bool
     */
    protected function hasVerifiedOtp($identity): bool
    {
        // toArray() leaves out hidden fields such as `secret`, so read the fields one by one
        if ($identity instanceof EntityInterface) {
            $secret = $identity->get('secret');
            $verified = $identity->get('secret_verified');
        } else {
            $secret = $identity['secret'] ?? null;
            $verified = $identity['secret_verified'] ?? null;
        }

        return !empty($secret) && !empty($verified);
    }

    /**
     * Whether the identity has a registered WebAuthn credential, under any domain.
     *
     * @param \Cake\Datasource\EntityInterface|array|null $identity Identity.
     * @return bool
     */
    protected function hasWebauthnCredential($identity): bool
    {
        // web-auth/webauthn-lib is optional; without it no credential can have been registered
        if (empty($identity) || !class_exists(PublicKeyCredentialSource::class)) {
            return false;
        }
        // read the stored keys from the user record; a WebAuthn adapter would need the WebAuthn
        // settings of the request's domain, which a domain that only uses OTP does not have
        $id = $identity instanceof EntityInterface ? $identity->get('id') : ($identity['id'] ?? null);
        if ($id === null) {
            return false;
        }
        $usersTable = $this->getUsersTable();
        $user = $usersTable->find()->where([$usersTable->aliasField('id') => $id])->first();

        return $user !== null && UserCredentialSourceRepository::userHasCredentialOnAnyDomain($user);
    }

    /**
     * Refuses to enroll a factor while another one is registered.
     *
     * @param bool $otherFactorRegistered Whether the identity has another registered factor.
     * @return void
     * @throws \Cake\Http\Exception\BadRequestException When another factor is registered.
     */
    protected function refuseEnrollmentIf(bool $otherFactorRegistered): void
    {
        if ($otherFactorRegistered) {
            throw new BadRequestException(
                __d('cake_d_c/api', 'Another second factor is already registered; verify with it instead')
            );
        }
    }
}
