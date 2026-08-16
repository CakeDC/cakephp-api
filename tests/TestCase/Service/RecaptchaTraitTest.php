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
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use CakeDC\Api\Service\FallbackService;
use CakeDC\Api\TestSuite\TestCase;

/**
 * Unit tests for the reCaptcha validation trait.
 */
class RecaptchaTraitTest extends TestCase
{
    protected string $domainKey = 'Api.reCaptcha.example$com';

    protected function tearDown(): void
    {
        Configure::delete('Api.reCaptcha');
        parent::tearDown();
    }

    protected function buildHarness(?object $recaptchaInstance = null, array $data = []): RecaptchaTraitHarness
    {
        $request = new ServerRequest([
            'url' => '/recaptcha',
            'environment' => [
                'HTTP_HOST' => 'example.com',
                'REQUEST_METHOD' => 'POST',
            ],
            'data' => $data,
        ]);
        $service = new FallbackService([
            'request' => $request,
            'response' => new Response(),
            'service' => 'recaptcha',
            'baseUrl' => '/recaptcha',
        ]);

        return new RecaptchaTraitHarness($service, $recaptchaInstance);
    }

    protected function buildVerifyStub(bool $success): object
    {
        return new class ($success) {
            public function __construct(private readonly bool $success)
            {
            }

            public function verify(mixed $response, mixed $remoteIp = null): object
            {
                return new class ($this->success) {
                    public function __construct(private readonly bool $success)
                    {
                    }

                    public function isSuccess(): bool
                    {
                        return $this->success;
                    }
                };
            }
        };
    }

    public function testDisabledReturnsTrue(): void
    {
        Configure::write($this->domainKey, ['disabled' => true]);
        $harness = $this->buildHarness();
        $this->assertTrue($harness->exposedValidateReCaptcha('any-token'));
    }

    public function testVerifySuccessWithExplicitResponse(): void
    {
        Configure::write($this->domainKey, ['secret' => 'secret']);
        $harness = $this->buildHarness($this->buildVerifyStub(true));
        $this->assertTrue($harness->exposedValidateReCaptcha('valid-token'));
    }

    public function testVerifyFailure(): void
    {
        Configure::write($this->domainKey, ['secret' => 'secret']);
        $harness = $this->buildHarness($this->buildVerifyStub(false));
        $this->assertFalse($harness->exposedValidateReCaptcha('invalid-token'));
    }

    public function testVerifyUsesRequestDataFallback(): void
    {
        Configure::write($this->domainKey, ['secret' => 'secret']);
        $harness = $this->buildHarness($this->buildVerifyStub(true), [
            'g-recaptcha-response' => 'from-request',
        ]);
        $this->assertTrue($harness->exposedValidateReCaptcha());
    }

    public function testNoSecretReturnsNullInstance(): void
    {
        Configure::write($this->domainKey, ['disabled' => false]);
        $harness = $this->buildHarness();
        $this->assertNull($harness->getReCaptchaInstance());
    }
}
