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

/**
 * Unit tests for JwtTokenTrait::getAudience().
 */
class JwtTokenTraitTest extends TestCase
{
    public $fixtures = [
        'plugin.CakeDC/Api.JwtRefreshTokens',
    ];

    public function setUp(): void
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

    public function tearDown(): void
    {
        Configure::delete('Users.table');
        Configure::delete('Api.Jwt');
        Configure::delete('Api.2fa');
        Configure::delete('Api.OneTimePasswordAuthenticator');
        parent::tearDown();
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

        return json_decode((string)base64_decode(strtr($parts[1], '-_', '+/')), true);
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
