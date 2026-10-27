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

namespace CakeDC\Api\Test\TestCase\Service\Extension;

use ArrayObject;
use Cake\Event\Event;
use Cake\Http\ServerRequest;
use Cake\Log\Log;
use Cake\ORM\Entity;
use CakeDC\Api\Service\Action\Result;
use CakeDC\Api\Service\Extension\LogExtension;
use CakeDC\Api\Service\RequestParser\BaseParser;
use CakeDC\Api\Service\Service;
use CakeDC\Api\TestSuite\TestCase;
use Exception;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use stdClass;

/**
 * The Log extension must not write passwords, tokens or secrets to the log.
 */
class LogExtensionRedactTest extends TestCase
{
    /**
     * @param array $config Extension config.
     * @return \ReflectionMethod[]|object[]
     */
    protected function redactor(array $config = []): array
    {
        $extension = (new ReflectionClass(LogExtension::class))->newInstanceWithoutConstructor();
        $extension->setConfig($config);
        $method = new ReflectionMethod($extension, '_redact');
        $method->setAccessible(true);

        return [$extension, $method];
    }

    public function testSensitiveValuesAreReplaced(): void
    {
        [$extension, $method] = $this->redactor();
        $result = $method->invoke($extension, [
            'username' => 'user-1',
            'password' => '12345',
            'new_password' => 'secret-value',
            'code' => 200,
            'data' => [
                'access_token' => 'header.payload.signature',
                'refresh_token' => 'refresh',
                'user' => ['api_token' => 'xyz', 'title' => 'kept', 'secret' => 'otp'],
            ],
        ]);
        $this->assertSame('user-1', $result['username']);
        $this->assertSame('***', $result['password']);
        $this->assertSame('***', $result['new_password']);
        $this->assertSame(200, $result['code']);
        $this->assertSame('***', $result['data']['access_token']);
        $this->assertSame('***', $result['data']['refresh_token']);
        $this->assertSame('***', $result['data']['user']['api_token']);
        $this->assertSame('***', $result['data']['user']['secret']);
        $this->assertSame('kept', $result['data']['user']['title']);
    }

    public function testTheListCanBeChanged(): void
    {
        [$extension, $method] = $this->redactor(['redact' => ['email']]);
        $result = $method->invoke($extension, ['email' => 'a@example.com', 'password' => 'p']);
        $this->assertSame('***', $result['email']);
        // the setting adds to the default words
        $this->assertSame('***', $result['password']);
    }

    public function testScalarsAreKept(): void
    {
        [$extension, $method] = $this->redactor();
        $this->assertSame('plain', $method->invoke($extension, 'plain'));
        $this->assertNull($method->invoke($extension, null));
    }

    public function testEntitiesAndNestedSerializablesAreRedacted(): void
    {
        [$extension, $method] = $this->redactor();
        $result = $method->invoke($extension, [
            'user' => new Entity(['username' => 'user-1', 'api_token' => 'xyz']),
        ]);
        $this->assertSame('user-1', $result['user']['username']);
        $this->assertSame('***', $result['user']['api_token']);
    }

    public function testEmptyWordsMaskNothing(): void
    {
        [$extension, $method] = $this->redactor();
        $extension->setConfig('redact', ['', false], false);
        $result = $method->invoke($extension, ['title' => 'kept']);
        $this->assertSame('kept', $result['title']);
    }

    public function testPlainObjectsAndTraversablesAreRedacted(): void
    {
        [$extension, $method] = $this->redactor();
        $body = new stdClass();
        $body->password = '12345';
        $body->title = 'kept';
        $result = $method->invoke($extension, ['body' => $body, 'list' => new ArrayObject(['api_token' => 'xyz'])]);
        $this->assertSame('***', $result['body']['password']);
        $this->assertSame('kept', $result['body']['title']);
        $this->assertSame('***', $result['list']['api_token']);
    }

    public function testExceptionTraceIsNotLogged(): void
    {
        Log::setConfig('log_redact_test', ['className' => 'Array', 'scopes' => ['api'], 'levels' => ['info']]);
        $result = new Result();
        $result->setException(new Exception('Request failed'));
        $parser = $this->createMock(BaseParser::class);
        $parser->method('getParams')->willReturn([]);
        $service = $this->createMock(Service::class);
        $service->method('getResult')->willReturn($result);
        $service->method('getBaseUrl')->willReturn('/articles');
        $service->method('getParser')->willReturn($parser);
        $service->method('getRequest')->willReturn(new ServerRequest());
        $extension = (new ReflectionClass(LogExtension::class))->newInstanceWithoutConstructor();
        $extension->setConfig([]);
        foreach (['_service' => $service, '_timer' => microtime(true)] as $name => $value) {
            $property = new ReflectionProperty(LogExtension::class, $name);
            $property->setAccessible(true);
            $property->setValue($extension, $value);
        }

        $extension->afterProcess(new Event('Service.afterDispatch', $service));
        $logged = implode("\n", Log::engine('log_redact_test')->read());
        Log::drop('log_redact_test');
        $this->assertStringContainsString('Request failed', $logged);
        $this->assertStringNotContainsString('exceptionStack', $logged);
    }
}
