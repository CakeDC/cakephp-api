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

namespace CakeDC\Api\Test\TestCase\Integration\Service\Action\Auth;

use Cake\Core\Configure;
use Cake\ORM\TableRegistry;
use CakeDC\Api\Rbac\Rules\TwoFactorPassedScope;
use CakeDC\Api\Service\ServiceRegistry;
use CakeDC\Api\Test\ConfigTrait;
use CakeDC\Api\TestSuite\IntegrationTestCase;
use DateTimeImmutable;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha512;
use Lcobucci\JWT\Signer\Key\InMemory;

/**
 * Class RefreshActionTest
 *
 * @package CakeDC\Api\Test\TestCase\Integration\Service\Action\Auth
 */
class JwtRefreshActionTest extends IntegrationTestCase
{
    use ConfigTrait;

    protected array $fixtures = [
        'plugin.CakeDC/Api.SocialAccounts',
        'plugin.CakeDC/Api.Users',
        'plugin.CakeDC/Api.Articles',
        'plugin.CakeDC/Api.Authors',
        'plugin.CakeDC/Api.Tags',
        'plugin.CakeDC/Api.ArticlesTags',
        'plugin.CakeDC/Api.JwtRefreshTokens',
    ];

    /**
     * setUp
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        TableRegistry::getTableLocator()->clear();
        $this->_authAccess();
        Configure::write('App.fullBaseUrl', 'http://example.com');
    }

    /**
     * tearDown
     *
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();
        Configure::write('Test.Api.Extension', null);
        Configure::delete('Test.Api.Config');
        Configure::delete('Test.Api.Permissions');
    }

    /**
     * Returns the decoded payload of a JWT without verifying it.
     *
     * @param string $token JWT.
     * @return array
     */
    protected function jwtPayload(string $token): array
    {
        $parts = explode('.', $token);
        $json = base64_decode(strtr($parts[1], '-_', '+/'));

        return json_decode((string)$json, true, 512, JSON_THROW_ON_ERROR);
    }

    public function testSuccessRefresh()
    {
        $this->sendRequest('/auth/jwt_login', 'POST', ['username' => 'user-1', 'password' => '12345']);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $this->assertTrue(is_array($result['data']));
        $this->assertTrue(array_key_exists('refresh_token', $result['data']));
        sleep(1);

        ServiceRegistry::getServiceLocator()->clear();
        $this->sendRequest('/auth/jwt_refresh', 'POST', [], null, [
            'Authorization' => $result['data']['refresh_token'],
        ]);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $this->assertSame('http://example.com/', $this->jwtPayload($result['data']['access_token'])['aud']);
    }

    public function testRefreshKeepsTwoFactorAudience(): void
    {
        $config = include dirname(__DIR__, 5) . DS . 'Config' . DS . 'api.php';
        $config['Api']['2fa']['enabled'] = true;
        $config['Api']['OneTimePasswordAuthenticator']['login'] = true;
        Configure::write('Test.Api.Config', $config);
        $twoFactorAudience = 'http://example.com/2fa';

        $this->sendRequest('/auth/jwt_login', 'POST', ['username' => 'user-1', 'password' => '12345']);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);
        $this->assertSame($twoFactorAudience, $this->jwtPayload($result['data']['access_token'])['aud']);
        $this->assertSame($twoFactorAudience, $this->jwtPayload($result['data']['refresh_token'])['aud']);
        sleep(1);

        ServiceRegistry::getServiceLocator()->clear();
        $this->sendRequest('/auth/jwt_refresh', 'POST', [], null, [
            'Authorization' => $result['data']['refresh_token'],
        ]);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);

        // a refreshed token must not skip the pending second factor
        $this->assertSame($twoFactorAudience, $this->jwtPayload($result['data']['access_token'])['aud']);
        $this->assertSame($twoFactorAudience, $this->jwtPayload($result['data']['refresh_token'])['aud']);
    }

    /**
     * Requests the articles index with a JWT access token.
     *
     * @param string $token Access token.
     * @return string HTTP code and JSend status, e.g. "200 success".
     */
    protected function articlesWithToken(string $token): string
    {
        ServiceRegistry::getServiceLocator()->clear();
        $this->sendRequest('/articles', 'GET', [], null, ['Authorization' => $token]);
        $body = json_decode((string)$this->_response->getBody(), true);

        return $this->_response->getStatusCode() . ' ' . ($body['status'] ?? 'no-jsend-body');
    }

    /**
     * Protects articles/index with TwoFactorPassedScope, leaving the auth service open.
     *
     * @return void
     */
    protected function requireTwoFactorPassedForArticles(): void
    {
        Configure::write('Test.Api.Permissions', [
            [
                'role' => '*',
                'service' => 'articles',
                'action' => 'index',
                'allowed' => new TwoFactorPassedScope(),
            ],
            [
                'role' => '*',
                'service' => '*',
                'action' => '*',
                'method' => '*',
                'bypassAuth' => true,
            ],
        ]);
    }

    /**
     * Logs user-1 in, then replaces the stored refresh token with one carrying the given audiences.
     *
     * @param string ...$audiences Audiences for the `aud` claim, none for no claim.
     * @return string The stored refresh token.
     */
    protected function storeRefreshToken(string ...$audiences): string
    {
        // Log in first: that loads the api config (so the signing secret is available) and
        // creates the user's jwt_refresh_tokens row, which we then replace.
        $this->sendRequest('/auth/jwt_login', 'POST', ['username' => 'user-1', 'password' => '12345']);
        $this->assertSuccess($this->getJsonResponse());

        $usersTable = TableRegistry::getTableLocator()->get('CakeDC/Users.Users');
        $user = $usersTable->find()->where(['username' => 'user-1'])->firstOrFail();

        $secret = Configure::read('Api.Jwt.RefreshToken.secret');
        $this->assertNotEmpty($secret);
        $config = Configuration::forSymmetricSigner(new Sha512(), InMemory::plainText((string)$secret));
        $timestamp = new DateTimeImmutable();
        $builder = $config->builder()
            ->issuedBy('http://example.com/')
            ->issuedAt($timestamp)
            ->expiresAt($timestamp->modify('+1 day'))
            ->relatedTo((string)$user->get('id'));
        if ($audiences !== []) {
            $builder = $builder->permittedFor(...$audiences);
        }
        $token = $builder->getToken($config->signer(), $config->signingKey())->toString();

        $table = TableRegistry::getTableLocator()->get('CakeDC/Api.JwtRefreshTokens');
        $entity = $table->find()->where([
            'model' => $usersTable->getAlias(),
            'foreign_key' => $user->get('id'),
        ])->firstOrFail();
        $entity->set('token', $token);
        $table->saveOrFail($entity);

        return $token;
    }

    public function testRefreshRejectsTokenWithoutAudience(): void
    {
        // A refresh token with no `aud` must be refused rather than fall through to the
        // default audience, which would hand out a token that no longer requires 2FA.
        $token = $this->storeRefreshToken();

        ServiceRegistry::getServiceLocator()->clear();
        $this->sendRequest('/auth/jwt_refresh', 'POST', [], null, ['Authorization' => $token]);
        $this->assertError($this->getJsonResponse(), 401);
    }

    public function testRefreshRejectsTokenWithArrayAudience(): void
    {
        // RFC 7519 allows a multi-valued `aud`; it cannot be carried into the new tokens
        $token = $this->storeRefreshToken('http://example.com/2fa', 'http://example.com/');

        ServiceRegistry::getServiceLocator()->clear();
        $this->sendRequest('/auth/jwt_refresh', 'POST', [], null, ['Authorization' => $token]);
        $this->assertError($this->getJsonResponse(), 401);
    }

    public function testRefreshRejectsTokenWithEmptyAudience(): void
    {
        $token = $this->storeRefreshToken('');

        ServiceRegistry::getServiceLocator()->clear();
        $this->sendRequest('/auth/jwt_refresh', 'POST', [], null, ['Authorization' => $token]);
        $this->assertError($this->getJsonResponse(), 401);
    }

    public function testPassedTokenIsAllowedBySameScope(): void
    {
        // Positive control for testRefreshedTokenCannotSkipSecondFactor. With 2FA disabled
        // the login audience is '/', which TwoFactorPassedScope must allow. Without this,
        // a blanket denial would make the bypass test pass for the wrong reason.
        $this->requireTwoFactorPassedForArticles();

        $this->sendRequest('/auth/jwt_login', 'POST', ['username' => 'user-1', 'password' => '12345']);
        $login = $this->getJsonResponse();
        $this->assertSuccess($login);
        $this->assertSame('http://example.com/', $this->jwtPayload($login['data']['access_token'])['aud']);

        $this->assertSame('200 success', $this->articlesWithToken($login['data']['access_token']));
    }

    public function testRefreshedTokenCannotSkipSecondFactor(): void
    {
        $config = include dirname(__DIR__, 5) . DS . 'Config' . DS . 'api.php';
        $config['Api']['2fa']['enabled'] = true;
        $config['Api']['OneTimePasswordAuthenticator']['login'] = true;
        Configure::write('Test.Api.Config', $config);
        $this->requireTwoFactorPassedForArticles();

        // step 1: password login, second factor still pending
        $this->sendRequest('/auth/jwt_login', 'POST', ['username' => 'user-1', 'password' => '12345']);
        $login = $this->getJsonResponse();
        $this->assertSuccess($login);
        $this->assertSame('http://example.com/2fa', $this->jwtPayload($login['data']['access_token'])['aud']);

        // step 2 (control): the 2FA-pending token is denied on the protected endpoint
        $pending = $this->articlesWithToken($login['data']['access_token']);
        $this->assertSame('200 error', $pending, 'Control failed: the 2FA-pending token was not denied, so the permission setup is wrong');
        sleep(1);

        // step 3: refresh with the 2FA-pending refresh token
        ServiceRegistry::getServiceLocator()->clear();
        $this->sendRequest('/auth/jwt_refresh', 'POST', [], null, [
            'Authorization' => $login['data']['refresh_token'],
        ]);
        $refresh = $this->getJsonResponse();
        $this->assertSuccess($refresh);

        // step 4: the refreshed token must still be denied
        $refreshed = $this->articlesWithToken($refresh['data']['access_token']);
        $this->assertSame(
            '200 error',
            $refreshed,
            sprintf('2FA bypass: pending token got "%s", refreshed token got "%s"', $pending, $refreshed),
        );
    }
}
