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
    /**
     * @param string $referer Referer header of the request.
     * @return \Cake\Http\ServerRequest
     */
    protected function request(string $referer = ''): ServerRequest
    {
        $environment = ['HTTP_HOST' => 'api.example.com'];
        if ($referer !== '') {
            $environment['HTTP_REFERER'] = $referer;
        }

        return new ServerRequest(['environment' => $environment]);
    }

    public function testDomainOfTheRequestHost(): void
    {
        // without a Referer the domain of the request is used, without its subdomain
        $this->assertSame('example$com', RequestParser::getDomain($this->request()));
        $this->assertSame('example.com', RequestParser::getDomain($this->request(), false));
    }

    public function testDomainOfTheReferer(): void
    {
        $this->assertSame('app$example$org', RequestParser::getDomain($this->request('https://app.example.org/page')));
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
                $this->assertSame('example$com', RequestParser::getDomain($this->request($referer)), $referer);
            }
        } finally {
            restore_error_handler();
        }

        $this->assertSame([], $warnings);
    }
}
