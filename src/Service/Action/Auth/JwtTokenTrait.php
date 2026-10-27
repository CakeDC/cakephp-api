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

use Cake\Core\Configure;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use Cake\Utility\Hash;
use CakeDC\Api\Exception\ValidationException;
use CakeDC\Api\Service\Auth\TwoFactorAuthentication\OneTimePasswordAuthenticationCheckerFactory;
use CakeDC\Api\Service\Auth\TwoFactorAuthentication\Webauthn2fAuthenticationCheckerFactory;
use DateInterval;
use DateTimeImmutable;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha512;
use Lcobucci\JWT\Signer\Key\InMemory;

trait JwtTokenTrait
{

    /**
     * Generates token response.
     *
     * @param \Cake\Datasource\EntityInterface|array $user User info.
     * @param string|null $type The type of token: `login` or `2fa`. Null means a refresh, which this
     *   method cannot issue because it takes no refresh token payload.
     * @throws \CakeDC\Api\Exception\ValidationException When `$type` is null.
     * @return array
     */
    public function generateTokenResponse($user, $type)
    {
        $timestamp = new DateTimeImmutable('-1 second');
        unset($user['additional_data'], $user['secret'], $user['secret_verified']);

        return Hash::merge($user, [
            'access_token' => $this->generateAccessToken($user, $timestamp, $type),
            'refresh_token' => $this->generateRefreshToken($user, $timestamp, $type),
            'expired' => $this->accessTokenLifeTime($timestamp),
            'enabled2FA' => $this->is2FAEnabled($user),
            'enabledWebauthn' => $this->isEnabledWebauthn2faAuthentication($user),
            'enabledOtp' => $this->isEnabledOneTimePasswordAuthentication($user),
        ]);
    }

    /**
     * Generates refresh token response.
     *
     * @param \Cake\Datasource\EntityInterface|array $user User info.
     * @param object|array|null $payload Payload of the refresh token; it must carry a non-empty string `aud`.
     * @throws \CakeDC\Api\Exception\ValidationException When a refresh payload has no non-empty string audience.
     * @return array
     */
    public function generateRefreshTokenResponse($user, $payload)
    {
        $timestamp = new DateTimeImmutable();

        return Hash::merge($user, [
            'access_token' => $this->generateAccessToken($user, $timestamp, null, $payload),
            'refresh_token' => $this->generateRefreshToken($user, $timestamp, null, $payload),
            'expired' => $this->accessTokenLifeTime($timestamp),
        ]);
    }

    /**
     * Generates access token.
     *
     * @param \Cake\Datasource\EntityInterface|array $user User info.
     * @param \DateTimeImmutable $timestamp Timestamp.
     * @param string|null $type The type of token: `login` or `2fa` issues new tokens, null means a
     *   refresh and requires a payload with a non-empty string `aud`.
     * @throws \CakeDC\Api\Exception\ValidationException When a refresh payload has no non-empty string audience.
     * @return bool|string
     */
    public function generateAccessToken($user, $timestamp, $type, $payload = null)
    {
        if (empty($user)) {
            return false;
        }

        $subject = $user['id'];
        $audience = $this->getAudience($user, $type, $payload);
        $issuer = Router::url('/', true);
        $signer = new Sha512();
        $secret = Configure::read('Api.Jwt.AccessToken.secret');

        $config = Configuration::forSymmetricSigner($signer, InMemory::plainText($secret));

        $token = $config->builder()
            ->issuedBy($issuer)
            ->issuedAt($timestamp) // Configures the time that the token was issue (iat claim)
            ->permittedFor($audience) // Configures the audience (aud claim)
            ->expiresAt($this->accessTokenLifeTime($timestamp)) // Configures the expiration time of the token (nbf claim)
            ->relatedTo((string)$subject) // Configures a new claim, called "sub"
            ->getToken($config->signer(), $config->signingKey()); // Retrieves the generated token

        return $token->toString();
    }

    /**
     * Get the audience for the token.
     *
     * @param \Cake\Datasource\EntityInterface|array $user User info.
     * @param string|null $type The type of token being generated.
     * @param object|array|null $payload Additional payload data.
     * @return string
     * @throws \CakeDC\Api\Exception\ValidationException When a refresh payload has no non-empty string audience.
     */
    public function getAudience($user, $type, $payload)
    {
        // the JWT authenticator returns the decoded payload as an object
        if (is_object($payload)) {
            $payload = (array)$payload;
        }
        if ($type === null) {
            // A refresh keeps the audience of the refresh token. A missing, empty or non-string
            // audience is refused: the default audience below would drop a pending second factor.
            $audience = is_array($payload) ? $payload['aud'] ?? null : null;
            if (!is_string($audience) || $audience === '') {
                throw new ValidationException('Invalid token provided', 401);
            }

            return $audience;
        }
        if ($type == 'login' && $this->is2FAEnabled($user)) {
            $audience = Router::url('/2fa', true);
        } else {
            $audience = Router::url('/', true);
        }

        return $audience;
    }

    protected function is2FAEnabled($user)
    {
        return $this->isEnabledWebauthn2faAuthentication($user) || $this->isEnabledOneTimePasswordAuthentication($user);
    }

    public function isEnabledWebauthn2faAuthentication($user)
    {
        $enabledTwoFactorVerify = Configure::read('Api.2fa.enabled');
        $webauthn2faChecker = $this->getWebauthn2fAuthenticationChecker();
        if ($enabledTwoFactorVerify && $webauthn2faChecker->isRequired((array)$user)) {
            return true;
        }

        return false;
    }

    public function isEnabledOneTimePasswordAuthentication($user)
    {
        $enabledTwoFactorVerify = Configure::read('Api.2fa.enabled');
        $otpChecker = $this->getOneTimePasswordAuthenticationChecker();
        if ($enabledTwoFactorVerify && $otpChecker->isRequired((array)$user)) {
            return true;
        }

        return false;
    }

    protected function getOneTimePasswordAuthenticationChecker()
    {
        return (new OneTimePasswordAuthenticationCheckerFactory())->build();
    }

    /**
     * Get the configured u2f authentication checker
     *
     * @return \CakeDC\Auth\Authentication\Webauthn2FAuthenticationCheckerInterface
     */
    protected function getWebauthn2fAuthenticationChecker()
    {
        return (new Webauthn2fAuthenticationCheckerFactory())->build();
    }

    /**
     * Generates refresh token.
     *
     * @param \Cake\Datasource\EntityInterface|array $user User info.
     * @param \DateTimeImmutable $timestamp Timestamp.
     * @param string|null $type The type of token: `login` or `2fa` issues new tokens, null means a
     *   refresh and requires a payload with a non-empty string `aud`.
     * @throws \CakeDC\Api\Exception\ValidationException When a refresh payload has no non-empty string audience.
     * @return bool|string
     */
    public function generateRefreshToken($user, $timestamp, $type, $payload = null)
    {
        if (empty($user)) {
            return false;
        }

        $subject = $user['id'];
        $audience = $this->getAudience($user, $type, $payload);
        $issuer = Router::url('/', true);
        $signer = new Sha512();
        $secret = Configure::read('Api.Jwt.RefreshToken.secret');

        $config = Configuration::forSymmetricSigner($signer, InMemory::plainText($secret));

        $token = $config->builder()
            ->issuedBy($issuer)
            ->issuedAt($timestamp) // Configures the time that the token was issue (iat claim)
            ->permittedFor($audience) // Configures the audience (aud claim)
            ->expiresAt($this->refreshTokenLifeTime($timestamp)) // Configures the expiration time of the token (nbf claim)
            ->relatedTo((string)$subject) // Configures a new claim, called "sub"
            ->getToken($config->signer(), $config->signingKey()); // Retrieves the generated token

        $rawToken = $token->toString();

        $modelAlias = Configure::read('Users.table');
        $UsersTable = TableRegistry::getTableLocator()->get($modelAlias);
        $model = $UsersTable->getAlias();

        $table = TableRegistry::getTableLocator()->get('CakeDC/Api.JwtRefreshTokens');
        $entity = $table->find()->where([
            'model' => $model,
            'foreign_key' => $subject,
        ])->first();
        $expired = $this->refreshTokenLifeTime($timestamp)->getTimeStamp();
        if ($entity) {
            $entity->token = $rawToken;
            $entity->expired = $expired;
        } else {
            $entity = $table->newEntity([
                'model' => $model,
                'foreign_key' => $subject,
                'token' => $rawToken,
                'expired' => $expired,
            ]);
        }
        $table->save($entity);

        return $rawToken;
    }

    /**
     * Generates access token with life time.
     *
     * @param \DateTimeImmutable $timestamp Timestamp.
     * @return \DateTimeImmutable
     */
    private function accessTokenLifeTime(DateTimeImmutable $timestamp): DateTimeImmutable
    {
        $accessTokenLifeTime = Configure::read('Api.Jwt.AccessToken.lifetime');

        return $timestamp->add(new DateInterval('PT' . $accessTokenLifeTime . 'S'));
    }

    /**
     * Generates refresh token with life time.
     *
     * @param \DateTimeImmutable $timestamp Timestamp.
     * @return \DateTimeImmutable
     */
    private function refreshTokenLifeTime(DateTimeImmutable $timestamp): DateTimeImmutable
    {
        $refreshTokenLifeTime = Configure::read('Api.Jwt.RefreshToken.lifetime');

        return $timestamp->add(new DateInterval('PT' . $refreshTokenLifeTime . 'S'));
    }
}
