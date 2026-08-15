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
use CakeDC\Api\Service\Auth\TwoFactorAuthentication\OneTimePasswordAuthenticationCheckerFactory;
use CakeDC\Api\Service\Auth\TwoFactorAuthentication\OneTimePasswordAuthenticationCheckerInterface;
use CakeDC\Api\Service\Auth\TwoFactorAuthentication\Webauthn2fAuthenticationCheckerFactory;
use CakeDC\Api\Service\Auth\TwoFactorAuthentication\Webauthn2fAuthenticationCheckerInterface;
use DateInterval;
use DateTimeImmutable;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha512;
use Lcobucci\JWT\Signer\Key\InMemory;

/**
 * JwtTokenTrait
 */
trait JwtTokenTrait
{
    /**
     * Generates token response.
     *
     * @param array $user User info.
     * @param string|null $type The type of token being generated.
     * @return array
     */
    public function generateTokenResponse(array $user, $type): array
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
     * @param array $user User info.
     * @param array $payload Additional payload data.
     * @return array
     */
    public function generateRefreshTokenResponse(array $user, $payload): array
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
     * @param string|null $type The type of token being generated.
     * @param array|null $payload Additional payload data.
     * @return false|string
     */
    public function generateAccessToken($user, \DateTimeImmutable $timestamp, $type, $payload = null): false|string
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
     * @param array|null $payload Additional payload data.
     * @return string
     */
    public function getAudience($user, $type, $payload): string
    {
        if ($type === null && is_array($payload) && isset($payload['aud'])) {
            return $payload['aud'];
        }
        if ($type == 'login' && $this->is2FAEnabled($user)) {
            return Router::url('/2fa', true);
        }

        return Router::url('/', true);
    }

    /**
     * Check if 2FA is enabled for the user.
     *
     * @param \Cake\Datasource\EntityInterface|array $user User info.
     * @return bool
     */
    protected function is2FAEnabled($user): bool
    {
        if ($this->isEnabledWebauthn2faAuthentication($user)) {
            return true;
        }

        return (bool)$this->isEnabledOneTimePasswordAuthentication($user);
    }

    /**
     * Check if Webauthn 2FA authentication is enabled for the user.
     *
     * @param \Cake\Datasource\EntityInterface|array $user User info.
     * @return bool
     */
    public function isEnabledWebauthn2faAuthentication($user): bool
    {
        $enabledTwoFactorVerify = Configure::read('Api.2fa.enabled');
        $webauthn2faChecker = $this->getWebauthn2fAuthenticationChecker();

        return $enabledTwoFactorVerify && $webauthn2faChecker->isRequired((array)$user);
    }

    /**
     * Check if One-Time Password authentication is enabled for the user.
     *
     * @param \Cake\Datasource\EntityInterface|array $user User info.
     * @return bool
     */
    public function isEnabledOneTimePasswordAuthentication($user): bool
    {
        $enabledTwoFactorVerify = Configure::read('Api.2fa.enabled');
        $otpChecker = $this->getOneTimePasswordAuthenticationChecker();

        return $enabledTwoFactorVerify && $otpChecker->isRequired((array)$user);
    }

    /**
     * Get the One-Time Password Authentication Checker.
     *
     * @return \CakeDC\Api\Service\Auth\TwoFactorAuthentication\OneTimePasswordAuthenticationCheckerInterface
     */
    protected function getOneTimePasswordAuthenticationChecker(): OneTimePasswordAuthenticationCheckerInterface
    {
        return (new OneTimePasswordAuthenticationCheckerFactory())->build();
    }

    /**
     * Get the configured u2f authentication checker
     *
     * @return \CakeDC\Api\Service\Auth\TwoFactorAuthentication\Webauthn2fAuthenticationCheckerInterface
     */
    protected function getWebauthn2fAuthenticationChecker(): Webauthn2fAuthenticationCheckerInterface
    {
        return (new Webauthn2fAuthenticationCheckerFactory())->build();
    }

    /**
     * Generates refresh token.
     *
     * @param \Cake\Datasource\EntityInterface|array $user User info.
     * @param \DateTimeImmutable $timestamp Timestamp.
     * @param string|null $type The type of token being generated.
     * @param array|null $payload Additional payload data.
     * @return false|string
     */
    public function generateRefreshToken($user, \DateTimeImmutable $timestamp, $type, $payload = null): false|string
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
        /** @var \CakeDC\Api\Model\Entity\JwtRefreshToken|null $entity */
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
