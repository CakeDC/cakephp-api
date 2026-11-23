<?php
declare(strict_types=1);

/**
 * Copyright 2016 - 2026, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2016 - 2026, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Api\Test\TestCase\Service;

use Cake\Core\Configure;
use Cake\Routing\Router;
use CakeDC\Api\Exception\ValidationException;
use CakeDC\Api\Test\Settings;
use CakeDC\Api\TestSuite\TestCase;
use DateTimeImmutable;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha512;
use Lcobucci\JWT\Signer\Key\InMemory;

/**
 * Unit tests for the JWT token generation trait.
 */
class JwtTokenTraitTest extends TestCase
{
    protected array $fixtures = [
        'plugin.CakeDC/Api.JwtRefreshTokens',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        Configure::write('App.fullBaseUrl', 'http://example.com');
        Configure::write('Users.table', 'CakeDC/Users.Users');
        Configure::write('Api.Jwt.AccessToken.secret', '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789');
        Configure::write('Api.Jwt.AccessToken.lifetime', 600);
        Configure::write('Api.Jwt.RefreshToken.secret', 'ZYXWVUTSRQPONMLKJIHGFEDCBAzyxwvutsrqponmlkjihgfedcba9876543210123456789');
        Configure::write('Api.Jwt.RefreshToken.lifetime', 1209600);
        Configure::write('Api.2fa.enabled', false);
    }

    protected function tearDown(): void
    {
        Configure::delete('Users.table');
        Configure::delete('Api.Jwt');
        Configure::delete('Api.2fa');
        Configure::delete('Api.OneTimePasswordAuthenticator');
        parent::tearDown();
    }

    protected function decode(string $token): \Lcobucci\JWT\Token\Plain
    {
        $config = Configuration::forSymmetricSigner(
            new Sha512(),
            InMemory::plainText('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789')
        );

        return $config->parser()->parse($token);
    }

    public function testGenerateAccessTokenHasClaims(): void
    {
        $harness = new JwtTokenHarness();
        $timestamp = new DateTimeImmutable('-1 second');
        $tokenString = $harness->generateAccessToken(['id' => 1], $timestamp, 'login');
        $this->assertIsString($tokenString);
        $token = $this->decode($tokenString);
        $this->assertSame('1', $token->claims()->get('sub'));
        $this->assertSame(Router::url('/', true), $token->claims()->get('iss'));
        $exp = $token->claims()->get('exp');
        $this->assertInstanceOf(DateTimeImmutable::class, $exp);
        $this->assertGreaterThan($timestamp, $exp);
    }

    public function testGenerateAccessTokenEmptyUserReturnsFalse(): void
    {
        $harness = new JwtTokenHarness();
        $this->assertFalse($harness->generateAccessToken([], new DateTimeImmutable(), 'login'));
    }

    public function testGetAudienceLoginWithout2fa(): void
    {
        $harness = new JwtTokenHarness();
        $this->assertSame(Router::url('/', true), $harness->getAudience(['id' => 1], 'login', null));
    }

    public function testGetAudienceLoginWith2fa(): void
    {
        Configure::write('Api.2fa.enabled', true);
        Configure::write('Api.OneTimePasswordAuthenticator.login', true);
        $harness = new JwtTokenHarness();
        $this->assertSame(Router::url('/2fa', true), $harness->getAudience(['id' => 1], 'login', null));
    }

    public function testGetAudienceFromPayload(): void
    {
        $harness = new JwtTokenHarness();
        $this->assertSame('custom-audience', $harness->getAudience(['id' => 1], null, ['aud' => 'custom-audience']));
    }

    public function testGenerateTokenResponseShape(): void
    {
        $harness = new JwtTokenHarness();
        $result = $harness->generateTokenResponse(['id' => 1, 'email' => 'a@b.c'], 'login');
        $this->assertArrayHasKey('access_token', $result);
        $this->assertArrayHasKey('refresh_token', $result);
        $this->assertArrayHasKey('expired', $result);
        $this->assertArrayHasKey('enabled2FA', $result);
        $this->assertArrayHasKey('enabledWebauthn', $result);
        $this->assertArrayHasKey('enabledOtp', $result);
        $this->assertFalse($result['enabled2FA']);
        $this->assertFalse($result['enabledOtp']);
    }

    public function testGenerateTokenResponse2faFlagsEnabled(): void
    {
        Configure::write('Api.2fa.enabled', true);
        Configure::write('Api.OneTimePasswordAuthenticator.login', true);
        $harness = new JwtTokenHarness();
        $result = $harness->generateTokenResponse(['id' => 1], 'login');
        $this->assertTrue($result['enabled2FA']);
        $this->assertTrue($result['enabledOtp']);
    }

    public function testGenerateTokenResponseRemovesSensitiveUserFields(): void
    {
        $harness = new JwtTokenHarness();
        $result = $harness->generateTokenResponse([
            'id' => 1,
            'secret' => 'should-be-removed',
            'secret_verified' => true,
        ], 'login');
        $this->assertArrayNotHasKey('secret', $result);
        $this->assertArrayNotHasKey('secret_verified', $result);
    }

    /**
     * Returns the claims of a JWT without verifying it.
     *
     * @param string $token JWT.
     * @return array
     */
    protected function claims(string $token): array
    {
        $parts = explode('.', $token);

        return json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
    }

    public function testGetAudienceFromArrayPayload(): void
    {
        $harness = new JwtTokenHarness();
        $this->assertSame('custom-audience', $harness->getAudience(['id' => 1], null, ['aud' => 'custom-audience']));
    }

    public function testGetAudienceRejectsArrayPayloadAudience(): void
    {
        // RFC 7519 allows a multi-valued `aud`, which cannot be carried into a new token
        $harness = new JwtTokenHarness();
        $this->expectException(ValidationException::class);
        $harness->getAudience(['id' => 1], null, (object)['aud' => [Router::url('/2fa', true), 'other']]);
    }

    public function testGetAudienceRejectsEmptyPayloadAudience(): void
    {
        $harness = new JwtTokenHarness();
        $this->expectException(ValidationException::class);
        $harness->getAudience(['id' => 1], null, ['aud' => '']);
    }

    public function testGetAudienceFromObjectPayloadKeepsTwoFactorAudience(): void
    {
        $harness = new JwtTokenHarness();
        $payload = (object)['aud' => Router::url('/2fa', true), 'sub' => 1];
        $this->assertSame(Router::url('/2fa', true), $harness->getAudience(['id' => 1], null, $payload));
    }

    public function testGetAudienceFromObjectPayloadKeepsPassedAudience(): void
    {
        $harness = new JwtTokenHarness();
        $payload = (object)['aud' => Router::url('/', true)];
        $this->assertSame(Router::url('/', true), $harness->getAudience(['id' => 1], null, $payload));
    }

    public function testGetAudienceRejectsRefreshPayloadWithoutAudience(): void
    {
        // a refresh (type null) must not fall back to the full-access default audience
        $harness = new JwtTokenHarness();
        foreach (['null' => null, 'empty array' => [], 'empty object' => (object)[], 'no aud' => ['sub' => 1], 'null aud' => ['aud' => null], 'non-string aud' => ['aud' => 1]] as $case => $payload) {
            try {
                $harness->getAudience(['id' => 1], null, $payload);
                $this->fail('No exception for a refresh payload with ' . $case);
            } catch (ValidationException $exception) {
                $this->assertSame(401, $exception->getCode(), $case);
            }
        }
    }

    public function testGetAudienceWithoutPayloadDefaultsForLogin(): void
    {
        // an explicit type still uses the computed audience
        $harness = new JwtTokenHarness();
        $this->assertSame(Router::url('/', true), $harness->getAudience(['id' => 1], 'login', null));
        $this->assertSame(Router::url('/', true), $harness->getAudience(['id' => 1], '2fa', []));
    }

    public function testGetAudienceLoginIgnoresPayloadAudience(): void
    {
        Configure::write('Api.2fa.enabled', true);
        Configure::write('Api.OneTimePasswordAuthenticator.login', true);
        $harness = new JwtTokenHarness();
        $payload = (object)['aud' => Router::url('/', true)];
        $this->assertSame(Router::url('/2fa', true), $harness->getAudience(['id' => 1], 'login', $payload));
    }

    public function testGenerateRefreshTokenResponseKeepsObjectPayloadAudience(): void
    {
        $harness = new JwtTokenHarness();
        $payload = (object)['aud' => Router::url('/2fa', true)];
        $result = $harness->generateRefreshTokenResponse(['id' => Settings::USER1], $payload);

        $this->assertSame(Router::url('/2fa', true), $this->claims($result['access_token'])['aud']);
        $this->assertSame(Router::url('/2fa', true), $this->claims($result['refresh_token'])['aud']);
    }
}
