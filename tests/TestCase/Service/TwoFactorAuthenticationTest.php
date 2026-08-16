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
use CakeDC\Api\Service\Auth\TwoFactorAuthentication\DefaultOneTimePasswordAuthenticationChecker;
use CakeDC\Api\Service\Auth\TwoFactorAuthentication\DefaultWebauthn2fAuthenticationChecker;
use CakeDC\Api\Service\Auth\TwoFactorAuthentication\OneTimePasswordAuthenticationCheckerFactory;
use CakeDC\Api\Service\Auth\TwoFactorAuthentication\Webauthn2fAuthenticationCheckerFactory;
use CakeDC\Api\TestSuite\TestCase;

/**
 * Unit tests for the 2FA authentication checkers and their factories.
 */
class TwoFactorAuthenticationTest extends TestCase
{
    protected function tearDown(): void
    {
        Configure::delete('Api.OneTimePasswordAuthenticator');
        Configure::delete('Api.Webauthn2fa');
        parent::tearDown();
    }

    public function testOtpCheckerDisabledByDefault(): void
    {
        $checker = new DefaultOneTimePasswordAuthenticationChecker();
        $this->assertFalse($checker->isEnabled());
    }

    public function testOtpCheckerEnabledWhenConfigured(): void
    {
        Configure::write('Api.OneTimePasswordAuthenticator.login', true);
        $checker = new DefaultOneTimePasswordAuthenticationChecker();
        $this->assertTrue($checker->isEnabled());
    }

    public function testOtpCheckerDisabled(): void
    {
        Configure::write('Api.OneTimePasswordAuthenticator.login', false);
        $checker = new DefaultOneTimePasswordAuthenticationChecker();
        $this->assertFalse($checker->isEnabled());
    }

    public function testOtpCheckerCustomEnableKey(): void
    {
        $checker = new DefaultOneTimePasswordAuthenticationChecker('Api.OneTimePasswordAuthenticator.custom');
        $this->assertTrue($checker->isEnabled());
        Configure::write('Api.OneTimePasswordAuthenticator.custom', false);
        $this->assertFalse($checker->isEnabled());
    }

    public function testOtpCheckerIsRequired(): void
    {
        $checker = new DefaultOneTimePasswordAuthenticationChecker();
        $this->assertFalse($checker->isRequired());
        $this->assertFalse($checker->isRequired([]));
        $this->assertFalse($checker->isRequired(['id' => 1]));
    }

    public function testOtpCheckerRequiredWhenEnabled(): void
    {
        Configure::write('Api.OneTimePasswordAuthenticator.login', true);
        $checker = new DefaultOneTimePasswordAuthenticationChecker();
        $this->assertTrue($checker->isRequired(['id' => 1]));
    }

    public function testWebauthnCheckerEnabledByDefault(): void
    {
        $checker = new DefaultWebauthn2fAuthenticationChecker();
        $this->assertTrue($checker->isEnabled());
    }

    public function testWebauthnCheckerDisabled(): void
    {
        Configure::write('Api.Webauthn2fa.enabled', false);
        $checker = new DefaultWebauthn2fAuthenticationChecker();
        $this->assertFalse($checker->isEnabled());
    }

    public function testWebauthnCheckerIsRequired(): void
    {
        $checker = new DefaultWebauthn2fAuthenticationChecker();
        $this->assertFalse($checker->isRequired([]));
        $this->assertTrue($checker->isRequired(['id' => 1]));
    }

    public function testOtpFactoryBuildsDefaultChecker(): void
    {
        Configure::write('Api.OneTimePasswordAuthenticator.checker', DefaultOneTimePasswordAuthenticationChecker::class);
        $factory = new OneTimePasswordAuthenticationCheckerFactory();
        $checker = $factory->build();
        $this->assertInstanceOf(DefaultOneTimePasswordAuthenticationChecker::class, $checker);
    }

    public function testOtpFactoryRejectsInvalidChecker(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Configure::write('Api.OneTimePasswordAuthenticator.checker', \stdClass::class);
        (new OneTimePasswordAuthenticationCheckerFactory())->build();
    }

    public function testWebauthnFactoryBuildsDefaultChecker(): void
    {
        Configure::write('Api.Webauthn2fa.checker', DefaultWebauthn2fAuthenticationChecker::class);
        $factory = new Webauthn2fAuthenticationCheckerFactory();
        $checker = $factory->build();
        $this->assertInstanceOf(DefaultWebauthn2fAuthenticationChecker::class, $checker);
    }

    public function testWebauthnFactoryRejectsInvalidChecker(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Configure::write('Api.Webauthn2fa.checker', \stdClass::class);
        (new Webauthn2fAuthenticationCheckerFactory())->build();
    }
}
