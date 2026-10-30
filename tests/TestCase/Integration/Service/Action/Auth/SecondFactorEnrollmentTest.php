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

namespace CakeDC\Api\Test\TestCase\Integration\Service\Action\Auth;

use Cake\Core\Configure;
use Cake\ORM\TableRegistry;
use CakeDC\Api\Test\ConfigTrait;
use CakeDC\Api\TestSuite\IntegrationTestCase;
use RobThree\Auth\TwoFactorAuth;

/**
 * A password login gives a `/2fa` token, which can enroll a second factor. Once the user has one
 * registered factor, that token enrolls no other one, under any domain.
 */
class SecondFactorEnrollmentTest extends IntegrationTestCase
{
    use ConfigTrait;
    use SoftwareAuthenticatorTrait;

    protected $fixtures = [
        'plugin.CakeDC/Api.SocialAccounts',
        'plugin.CakeDC/Api.Users',
        'plugin.CakeDC/Api.JwtRefreshTokens',
        'plugin.CakeDC/Api.AuthStore',
    ];

    /**
     * Relying party id and origin, from `Api.Webauthn2fa.localhost` in tests/Config/api.php.
     */
    protected const RP_ID = 'localhost';

    protected const ORIGIN = 'https://localhost';

    protected const SECRET = 'JBSWY3DPEHPK3PXP';

    protected const OTHER_DOMAIN = 'https://other.test/';

    public function setUp(): void
    {
        parent::setUp();
        TableRegistry::getTableLocator()->clear();
        $this->_authAccess();
        Configure::write('App.fullBaseUrl', 'http://example.com');
        $config = include dirname(__DIR__, 5) . DS . 'Config' . DS . 'api.php';
        $config['Api']['2fa']['enabled'] = true;
        $config['Api']['OneTimePasswordAuthenticator']['login'] = true;
        // a second WebAuthn domain, chosen by the Referer header
        $config['Api']['Webauthn2fa']['other$test'] = ['appName' => 'other', 'id' => self::RP_ID];
        Configure::write('Test.Api.Config', $config);
        Configure::write('Test.Api.Authentication', true);
        $this->setOtp(null, false);
    }

    public function tearDown(): void
    {
        parent::tearDown();
        Configure::delete('Test.Api.Config');
        Configure::delete('Test.Api.Authentication');
    }

    /**
     * @param string|null $secret OTP secret of user-1.
     * @param bool $verified Whether the secret is verified.
     * @return void
     */
    protected function setOtp(?string $secret, bool $verified): void
    {
        TableRegistry::getTableLocator()->get('CakeDC/Users.Users')->updateQuery()
            ->set(['secret' => $secret, 'secret_verified' => $verified])
            ->where(['username' => 'user-1'])
            ->execute();
    }

    /**
     * Skips a test that uses OTP when robthree/twofactorauth, which OTP needs, is not installed.
     *
     * @return void
     */
    protected function requireOtp(): void
    {
        if (!class_exists(TwoFactorAuth::class)) {
            $this->markTestSkipped('robthree/twofactorauth is not installed');
        }
    }

    /**
     * Asserts that the request was refused and returned no token or secret.
     *
     * @param array $result JSend body.
     * @return void
     */
    protected function assertRefused(array $result): void
    {
        $this->assertSame('error', $result['status'] ?? null, (string)$this->_response->getBody());
        $this->assertArrayNotHasKey('access_token', (array)($result['data'] ?? []));
        $this->assertArrayNotHasKey('secret', (array)($result['data'] ?? []));
    }

    public function testWebauthnRegistrationWorks(): void
    {
        [$login] = $this->loginAndRegister();
        $this->assertNotEmpty($login['access_token']);
    }

    public function testWebauthnCannotBeEnrolledWhenOtpIsVerified(): void
    {
        $this->setOtp(self::SECRET, true);
        $login = $this->login();

        $this->assertRefused($this->withToken('/auth/webauthn2fa_register_options', 'GET', [], $login['access_token']));
        $this->assertRefused($this->withToken(
            '/auth/webauthn2fa_register',
            'POST',
            $this->attestation($this->newKey(), random_bytes(16), static::b64url(random_bytes(32))),
            $login['access_token'],
        ));
    }

    public function testWebauthnCanBeEnrolledWhileOtpIsOnlyStarted(): void
    {
        // an unverified secret is not a registered factor
        $this->setOtp(self::SECRET, false);
        [$login] = $this->loginAndRegister();
        $this->assertNotEmpty($login['access_token']);
    }

    public function testOtpCannotBeEnrolledWhenWebauthnIsRegistered(): void
    {
        $this->requireOtp();
        [$login] = $this->loginAndRegister();

        $this->assertRefused($this->withToken('/auth/otp_verify', 'GET', [], $login['access_token']));

        // a secret provisioned before the key was registered cannot be verified either
        $this->setOtp(self::SECRET, false);
        $tfa = new TwoFactorAuth();
        $this->assertRefused($this->withToken(
            '/auth/otp_verify_check',
            'POST',
            ['code' => $tfa->getCode(self::SECRET)],
            $login['access_token'],
        ));
    }

    public function testVerifiedOtpStillWorksWhenWebauthnIsAlsoRegistered(): void
    {
        $this->requireOtp();
        // a user who registered both factors earlier can still pass the second step with OTP
        [$login] = $this->loginAndRegister();
        $this->setOtp(self::SECRET, true);
        $tfa = new TwoFactorAuth();

        $check = $this->withToken('/auth/otp_verify_check', 'POST', ['code' => $tfa->getCode(self::SECRET)], $login['access_token']);
        $this->assertSame('success', $check['status'] ?? null, (string)$this->_response->getBody());
    }

    public function testWebauthnCannotBeRegisteredUnderAnotherDomain(): void
    {
        [$login] = $this->loginAndRegister();

        $options = $this->withToken('/auth/webauthn2fa_register_options', 'GET', [], $login['access_token'], self::OTHER_DOMAIN);
        $this->assertRefused($options);
        $this->assertRefused($this->withToken(
            '/auth/webauthn2fa_register',
            'POST',
            $this->attestation($this->newKey(), random_bytes(16), (string)($options['data']['challenge'] ?? static::b64url(random_bytes(32)))),
            $login['access_token'],
            self::OTHER_DOMAIN,
        ));
    }

    public function testOtpCannotBeEnrolledWhenWebauthnIsRegisteredUnderAnotherDomain(): void
    {
        $this->requireOtp();
        [$login] = $this->loginAndRegister();

        $this->assertRefused($this->withToken('/auth/otp_verify', 'GET', [], $login['access_token'], self::OTHER_DOMAIN));
    }

    public function testOtpCanBeEnrolledOnADomainWithoutWebauthnSettings(): void
    {
        if (!class_exists(TwoFactorAuth::class)) {
            $this->markTestSkipped('robthree/twofactorauth is not installed');
        }
        $login = $this->login();

        $verify = $this->withToken('/auth/otp_verify', 'GET', [], $login['access_token'], 'https://unconfigured.test/');
        $this->assertSame('success', $verify['status'] ?? null, (string)$this->_response->getBody());
        $this->assertNotEmpty($verify['data']['secret'] ?? null);
    }

    public function testOtpCanBeEnrolledWithoutTheLocalhostWebauthnEntry(): void
    {
        if (!class_exists(TwoFactorAuth::class)) {
            $this->markTestSkipped('robthree/twofactorauth is not installed');
        }
        $config = Configure::read('Test.Api.Config');
        $config['Api']['Webauthn2fa']['localhost'] = null;
        Configure::write('Test.Api.Config', $config);
        $login = $this->login();

        $verify = $this->withToken('/auth/otp_verify', 'GET', [], $login['access_token']);
        $this->assertSame('success', $verify['status'] ?? null, (string)$this->_response->getBody());
        $this->assertNotEmpty($verify['data']['secret'] ?? null);
    }

    public function testTwoFactorStatusOnADomainWithoutWebauthnSettings(): void
    {
        [$login] = $this->loginAndRegister();

        $status = $this->withToken('/auth/two_factor_auth', 'GET', [], $login['access_token'], 'https://unconfigured.test/');
        $this->assertSame('success', $status['status'] ?? null, (string)$this->_response->getBody());
        $this->assertTrue($status['data']['hasWebauthn']);
    }

    public function testTwoFactorStatusReportsBothFactors(): void
    {
        [$login] = $this->loginAndRegister();
        $this->setOtp(self::SECRET, true);

        $status = $this->withToken('/auth/two_factor_auth', 'GET', [], $login['access_token'], self::OTHER_DOMAIN);
        $this->assertSame('success', $status['status'] ?? null, (string)$this->_response->getBody());
        $this->assertTrue($status['data']['hasOtp']);
        $this->assertTrue($status['data']['hasWebauthn']);
    }
}
