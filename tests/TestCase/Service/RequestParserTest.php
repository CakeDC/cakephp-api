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
    protected function tearDown(): void
    {
        unset($_SERVER['HTTP_REFERER']);
        parent::tearDown();
    }

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
        $_SERVER['HTTP_REFERER'] = 'https://referer.example.org/path';
        $request = new ServerRequest([
            'url' => '/articles',
            'environment' => ['HTTP_HOST' => 'example.com'],
        ]);
        $this->assertSame('referer$example$org', RequestParser::getDomain($request));
    }
}
