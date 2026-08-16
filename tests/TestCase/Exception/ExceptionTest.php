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

namespace CakeDC\Api\Test\TestCase\Exception;

use Cake\Core\Exception\CakeException;
use CakeDC\Api\Exception\MethodNotAllowedException;
use CakeDC\Api\Exception\ServiceException;
use CakeDC\Api\Exception\UnauthenticatedException;
use CakeDC\Api\Exception\UnauthorizedException;
use CakeDC\Api\Exception\ValidationException;
use CakeDC\Api\TestSuite\TestCase;

/**
 * Unit tests for the API exception classes.
 */
class ExceptionTest extends TestCase
{
    public function testMethodNotAllowedDefaults(): void
    {
        $e = new MethodNotAllowedException();
        $this->assertInstanceOf(CakeException::class, $e);
        $this->assertSame('Method not allowed', $e->getMessage());
        $this->assertSame(405, $e->getCode());
    }

    public function testMethodNotAllowedCustom(): void
    {
        $e = new MethodNotAllowedException('Custom message', 418);
        $this->assertSame('Custom message', $e->getMessage());
        $this->assertSame(418, $e->getCode());
    }

    public function testMethodNotAllowedSetFileLine(): void
    {
        $e = new MethodNotAllowedException();
        $e->setFile('/tmp/file.php');
        $e->setLine(42);
        $this->assertSame('/tmp/file.php', $e->getFile());
        $this->assertSame(42, $e->getLine());
    }

    public function testServiceExceptionDefaults(): void
    {
        $e = new ServiceException();
        $this->assertSame(500, $e->getCode());
    }

    public function testUnauthenticatedExceptionDefaults(): void
    {
        $e = new UnauthenticatedException();
        $this->assertSame(401, $e->getCode());
    }

    public function testUnauthorizedExceptionDefaults(): void
    {
        $e = new UnauthorizedException();
        $this->assertSame(403, $e->getCode());
    }

    public function testValidationExceptionDefaults(): void
    {
        $e = new ValidationException();
        $this->assertInstanceOf(ServiceException::class, $e);
        $this->assertSame('Validation errors', $e->getMessage());
        $this->assertSame(422, $e->getCode());
        $this->assertSame([], $e->getValidationErrors());
    }

    public function testValidationExceptionWithErrors(): void
    {
        $errors = ['title' => ['_required' => 'This field is required']];
        $e = new ValidationException('Invalid', 0, null, $errors);
        $this->assertSame($errors, $e->getValidationErrors());
    }

    public function testValidationExceptionSetErrors(): void
    {
        $e = new ValidationException();
        $e->setValidationErrors(['field' => 'error']);
        $this->assertSame(['field' => 'error'], $e->getValidationErrors());
    }

    public function testValidationExceptionCustomCode(): void
    {
        $e = new ValidationException('Invalid', 400);
        $this->assertSame(400, $e->getCode());
    }
}
