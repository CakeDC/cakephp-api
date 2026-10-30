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

namespace CakeDC\Api\Test\TestCase\Service\Action\Traits;

use Cake\Core\Configure;
use Cake\Http\ServerRequest;
use Cake\Log\Log;
use Cake\TestSuite\TestCase;
use CakeDC\Api\Service\Action\Traits\ReCaptchaTrait;

/**
 * Only the global setting turns reCaptcha validation off.
 */
class ReCaptchaTraitTest extends TestCase
{
    public function tearDown(): void
    {
        Configure::delete('Api.reCaptcha');
        unset($_SERVER['HTTP_REFERER']);
        parent::tearDown();
    }

    /**
     * @param bool|null $success Result of the verification, or null for no reCaptcha instance.
     * @return object
     */
    protected function subject(?bool $success)
    {
        $request = new ServerRequest(['environment' => ['HTTP_HOST' => 'example.com']]);

        return new class ($request, $success) {
            use ReCaptchaTrait;

            public $request;

            public $success;

            public function __construct($request, $success)
            {
                $this->request = $request;
                $this->success = $success;
            }

            public function getService()
            {
                $request = $this->request;

                return new class ($request) {
                    private $request;

                    public function __construct($request)
                    {
                        $this->request = $request;
                    }

                    public function getRequest()
                    {
                        return $this->request;
                    }
                };
            }

            protected function _getReCaptchaInstance()
            {
                if ($this->success === null) {
                    return null;
                }
                $success = $this->success;

                return new class ($success) {
                    private $success;

                    public function __construct($success)
                    {
                        $this->success = $success;
                    }

                    public function verify($response, $ip)
                    {
                        $success = $this->success && $response === 'valid';

                        return new class ($success) {
                            private $success;

                            public function __construct($success)
                            {
                                $this->success = $success;
                            }

                            public function isSuccess()
                            {
                                return $this->success;
                            }
                        };
                    }
                };
            }
        };
    }

    public function testGlobalDisabledSkipsValidation(): void
    {
        Configure::write('Api.reCaptcha.disabled', true);
        $this->assertTrue($this->subject(false)->validateReCaptcha('any'));
    }

    public function testValidTokenPasses(): void
    {
        $this->assertTrue($this->subject(true)->validateReCaptcha('valid'));
        $this->assertFalse($this->subject(true)->validateReCaptcha('invalid'));
    }

    public function testDisabledForTheDomainStillValidates(): void
    {
        Configure::write('Api.reCaptcha.example$com', ['disabled' => true, 'secret' => 'secret']);
        $this->assertFalse($this->subject(true)->validateReCaptcha('invalid'));
    }

    public function testDisabledForAnotherDomainStillValidates(): void
    {
        Configure::write('Api.reCaptcha.dev$test', ['disabled' => true]);
        $_SERVER['HTTP_REFERER'] = 'https://dev.test/';
        $this->assertFalse($this->subject(true)->validateReCaptcha('invalid'));
    }

    public function testNoReCaptchaInstanceFails(): void
    {
        $this->assertFalse($this->subject(null)->validateReCaptcha('valid'));
    }

    public function testGlobalDisabledMustBeTrue(): void
    {
        // a string from the environment does not turn validation off
        Configure::write('Api.reCaptcha.disabled', '1');
        $this->assertFalse($this->subject(true)->validateReCaptcha('invalid'));
    }

    public function testNoSecretFails(): void
    {
        // the real lookup: no secret for the domain means no reCaptcha instance
        Configure::write('Api.reCaptcha.example$com', ['disabled' => true]);
        $request = new ServerRequest(['environment' => ['HTTP_HOST' => 'example.com']]);
        $subject = new class ($request) {
            use ReCaptchaTrait;

            public $request;

            public function __construct($request)
            {
                $this->request = $request;
            }

            public function getService()
            {
                $request = $this->request;

                return new class ($request) {
                    private $request;

                    public function __construct($request)
                    {
                        $this->request = $request;
                    }

                    public function getRequest()
                    {
                        return $this->request;
                    }
                };
            }
        };

        $this->assertFalse($subject->validateReCaptcha('valid'));
    }

    public function testDisabledForTheDomainIsLogged(): void
    {
        // like the loggers of the application skeleton, which take messages without a scope
        Log::setConfig('recaptcha_test', ['className' => 'Array', 'levels' => ['warning'], 'scopes' => false]);
        Configure::write('Api.reCaptcha.example$com', ['disabled' => true]);
        try {
            $this->subject(true)->validateReCaptcha('invalid');
            $logged = implode("\n", Log::engine('recaptcha_test')->read());
        } finally {
            Log::drop('recaptcha_test');
        }

        $this->assertStringContainsString('`Api.reCaptcha.example$com.disabled` is ignored', $logged);
    }
}
