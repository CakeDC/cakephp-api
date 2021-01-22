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

use DateInterval;
use DateTimeImmutable;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Hmac\Sha512;

trait JwtTokenTrait
{
    /**
     * Generates token response.
     *
     * @param \Cake\Datasource\EntityInterface|array $user User info.
     * @return array
     */
    public function generateTokenResponse($user)
    {
        //$timestamp = time();
        $timestamp = new DateTimeImmutable();

        return Hash::merge($user, [
            'access_token' => $this->generateAccessToken($user, $timestamp),
            'refresh_token' => $this->generateRefreshToken($user, $timestamp),
            'expired' => $this->accessTokenLifeTime($timestamp),
        ]);
    }

    /**
     * Generates access token.
     *
     * @param \Cake\Datasource\EntityInterface|array $user User info.
     * @param DateTimeImmutable $timestamp Timestamp.
     * @return bool|string
     */
    public function generateAccessToken($user, $timestamp)
    {
        if (empty($user)) {
            return false;
        }

        $subject = $user['id'];
        $audience = Router::url('/', true);
        $issuer = Router::url('/', true);
        $signer = new Sha256();
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
     * Generates refresh token.
     *
     * @param \Cake\Datasource\EntityInterface|array $user User info.
     * @param DateTimeImmutable $timestamp Timestamp.
     * @return bool|string
     */
    public function generateRefreshToken($user, $timestamp)
    {
        if (empty($user)) {
            return false;
        }

        $subject = $user['id'];
        $audience = Router::url('/', true);
        $issuer = Router::url('/', true);
        $signer = new Sha512();
        $secret = Configure::read('Api.Jwt.RefreshToken.secret');

        $config = Configuration::forSymmetricSigner($signer, InMemory::plainText($secret));

        $token = $config->builder()
            ->issuedBy($issuer)
            ->issuedAt($timestamp) // Configures the time that the token was issue (iat claim)
            ->permittedFor($audience) // Configures the audience (aud claim)
            ->expiresAt($this->refreshTokenLifeTime($timestamp)) // Configures the expiration time of the token (nbf claim)
            ->relatedTo($subject) // Configures a new claim, called "sub"
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
     * @param DateTimeImmutable $timestamp Timestamp.
     * @return DateTimeImmutable
     */
    private function accessTokenLifeTime(DateTimeImmutable $timestamp): DateTimeImmutable
    {
        $accessTokenLifeTime = Configure::read('Api.Jwt.AccessToken.lifetime');

        return $timestamp->add(new DateInterval("PT" . $accessTokenLifeTime . "S"));
    }

    /**
     * Generates refresh token with life time.
     *
     * @param DateTimeImmutable $timestamp Timestamp.
     * @return DateTimeImmutable
     */
    private function refreshTokenLifeTime(DateTimeImmutable $timestamp): DateTimeImmutable
    {
        $refreshTokenLifeTime = Configure::read('Api.Jwt.RefreshToken.lifetime');

        return $timestamp->add(new DateInterval("PT" . $refreshTokenLifeTime . "S"));
    }
}
