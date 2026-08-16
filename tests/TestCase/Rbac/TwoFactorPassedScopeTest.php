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

namespace CakeDC\Api\Test\TestCase\Rbac;

use Authentication\Authenticator\JwtAuthenticator;
use Cake\Http\ServerRequest;
use Cake\Routing\Router;
use CakeDC\Api\Rbac\Rules\TwoFactorPassedScope;
use CakeDC\Api\TestSuite\TestCase;

/**
 * Unit tests for the 2FA passed RBAC scope rule (JWT aud claim == root URL).
 */
class TwoFactorPassedScopeTest extends TestCase
{
    protected function buildRequest(?object $provider): ServerRequest
    {
        $authentication = new class ($provider) {
            public function __construct(private readonly ?object $provider)
            {
            }

            public function getAuthenticationProvider(): ?object
            {
                return $this->provider;
            }
        };

        $request = new ServerRequest(['url' => '/articles']);

        return $request->withAttribute('authentication', $authentication);
    }

    protected function buildJwtAuthenticator(string $aud): JwtAuthenticator
    {
        $payload = new \stdClass();
        $payload->aud = $aud;

        $authenticator = new class () extends JwtAuthenticator {
            public function __construct()
            {
            }

            public function setTestPayload(?object $payload): void
            {
                $this->payload = $payload;
            }
        };
        $authenticator->setTestPayload($payload);

        return $authenticator;
    }

    public function testNoAuthenticationAttributeDenies(): void
    {
        $request = new ServerRequest(['url' => '/articles']);
        $scope = new TwoFactorPassedScope();
        $this->assertFalse($scope->allowed(['id' => 1], 'user', $request));
    }

    public function testNoProviderDenies(): void
    {
        $scope = new TwoFactorPassedScope();
        $this->assertFalse($scope->allowed(['id' => 1], 'user', $this->buildRequest(null)));
    }

    public function testNonJwtProviderDenies(): void
    {
        $scope = new TwoFactorPassedScope();
        $this->assertFalse($scope->allowed(['id' => 1], 'user', $this->buildRequest(new \stdClass())));
    }

    public function testAudMatchAllows(): void
    {
        $aud = Router::url('/', true);
        $request = $this->buildRequest($this->buildJwtAuthenticator($aud));
        $scope = new TwoFactorPassedScope();
        $this->assertTrue($scope->allowed(['id' => 1], 'user', $request));
    }

    public function testAudMismatchDenies(): void
    {
        $request = $this->buildRequest($this->buildJwtAuthenticator('http://example.com/other'));
        $scope = new TwoFactorPassedScope();
        $this->assertFalse($scope->allowed(['id' => 1], 'user', $request));
    }
}
