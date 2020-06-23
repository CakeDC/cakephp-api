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
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Hmac\Sha512;
use Lcobucci\JWT\Signer\Key;

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
        $timestamp = time();

        $accessTokenLifeTime = Configure::read('Api.Jwt.AccessToken.lifetime');

        return Hash::merge($user, [
            'access_token' => $this->generateAccessToken($user, $timestamp),
            'refresh_token' => $this->generateRefreshToken($user, $timestamp),
            'expired' => $timestamp + $accessTokenLifeTime,
        ]);
    }

    /**
     * Generates access token.
     *
     * @param \Cake\Datasource\EntityInterface|array $user User info.
     * @param int $timestamp Timestamp.
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
        $accessTokenLifeTime = Configure::read('Api.Jwt.AccessToken.lifetime');

        $token = (new Builder())
            ->issuedBy($issuer)
            ->issuedAt($timestamp) // Configures the time that the token was issue (iat claim)
            ->permittedFor($audience) // Configures the audience (aud claim)
            ->expiresAt($timestamp + $accessTokenLifeTime) // Configures the expiration time of the token (nbf claim)
            ->relatedTo($subject) // Configures a new claim, called "sub"
            ->getToken($signer, new Key($secret)); // Retrieves the generated token

        return (string)$token;
    }

    /**
     * Generates refresh token.
     *
     * @param \Cake\Datasource\EntityInterface|array $user User info.
     * @param int $timestamp Timestamp.
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
        $refreshTokenLifeTime = Configure::read('Api.Jwt.RefreshToken.lifetime');
        $expireTime = $timestamp + $refreshTokenLifeTime;

        $token = (new Builder())
            ->issuedBy($issuer)
            ->issuedAt($timestamp) // Configures the time that the token was issue (iat claim)
            ->permittedFor($audience) // Configures the audience (aud claim)
            ->expiresAt($expireTime) // Configures the expiration time of the token (nbf claim)
            ->relatedTo($subject) // Configures a new claim, called "sub"
            ->getToken($signer, new Key($secret)); // Retrieves the generated token

        $rawToken = (string)$token;

        $modelAlias = Configure::read('Users.table');
        $UsersTable = TableRegistry::getTableLocator()->get($modelAlias);
        $model = $UsersTable->getAlias();

        $table = TableRegistry::getTableLocator()->get('CakeDC/Api.JwtRefreshTokens');
        $entity = $table->find()->where([
            'model' => $model,
            'foreign_key' => $subject,
        ])->first();
        if ($entity) {
            $entity->token = $rawToken;
            $entity->expired = $expireTime;
        } else {
            $entity = $table->newEntity([
                'model' => $model,
                'foreign_key' => $subject,
                'token' => $rawToken,
                'expired' => $expireTime,
            ]);
        }
        $table->save($entity);

        return $rawToken;
    }
}
