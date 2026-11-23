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

use Cake\Http\ServerRequest;
use CakeDC\Api\TestSuite\TestCase;
use CakeDC\Api\Utility\RequestParser;

/**
 * Unit tests for the request domain parser helper.
 */
class RequestParserTest extends TestCase
{
    public function testGetDomainFromRequestHost(): void
    {
        $request = new ServerRequest([
            'url' => '/articles',
            'environment' => ['HTTP_HOST' => 'example.com'],
        ]);
        $this->assertSame('example$com', RequestParser::getDomain($request));
    }

    public function testGetDomainFromSubdomain(): void
    {
        $request = new ServerRequest([
            'url' => '/articles',
            'environment' => ['HTTP_HOST' => 'api.example.com'],
        ]);
        $this->assertSame('example$com', RequestParser::getDomain($request));
    }

    public function testGetDomainWithoutReplace(): void
    {
        $request = new ServerRequest([
            'url' => '/articles',
            'environment' => ['HTTP_HOST' => 'example.com'],
        ]);
        $this->assertSame('example.com', RequestParser::getDomain($request, false));
    }

    public function testGetDomainPrefersReferer(): void
    {
        $request = new ServerRequest([
            'url' => '/articles',
            'environment' => ['HTTP_HOST' => 'example.com', 'HTTP_REFERER' => 'https://referer.example.org/path'],
        ]);
        $this->assertSame('referer$example$org', RequestParser::getDomain($request));
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
                $request = new ServerRequest([
                    'url' => '/articles',
                    'environment' => ['HTTP_HOST' => 'api.example.com', 'HTTP_REFERER' => $referer],
                ]);
                $this->assertSame('example$com', RequestParser::getDomain($request), $referer);
            }
        } finally {
            restore_error_handler();
        }

        $this->assertSame([], $warnings);
    }
}
