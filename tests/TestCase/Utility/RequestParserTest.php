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

namespace CakeDC\Api\Test\TestCase\Utility;

use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use CakeDC\Api\Utility\RequestParser;

/**
 * The request domain comes from the Referer header, or from the request host.
 */
class RequestParserTest extends TestCase
{
    public function tearDown(): void
    {
        unset($_SERVER['HTTP_REFERER']);
        parent::tearDown();
    }

    /**
     * @return \Cake\Http\ServerRequest
     */
    protected function request(): ServerRequest
    {
        return new ServerRequest(['environment' => ['HTTP_HOST' => 'api.example.com']]);
    }

    public function testDomainOfTheRequestHost(): void
    {
        $this->assertSame('example$com', RequestParser::getDomain($this->request()));
        $this->assertSame('example.com', RequestParser::getDomain($this->request(), false));
    }

    public function testDomainOfTheReferer(): void
    {
        $_SERVER['HTTP_REFERER'] = 'https://app.example.org/page';
        $this->assertSame('app$example$org', RequestParser::getDomain($this->request()));
    }

    public function testRefererWithoutAHostUsesTheRequestHost(): void
    {
        $warnings = [];
        set_error_handler(function (int $level, string $message) use (&$warnings): bool {
            $warnings[] = $message;

            return true;
        });
        try {
            foreach (['foo', 'http:///x'] as $referer) {
                $_SERVER['HTTP_REFERER'] = $referer;
                $this->assertSame('example$com', RequestParser::getDomain($this->request()), $referer);
            }
        } finally {
            restore_error_handler();
        }

        $this->assertSame([], $warnings);
    }
}
