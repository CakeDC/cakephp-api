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

use CakeDC\Api\Service\Action\Result;
use CakeDC\Api\Service\ServiceRegistry;
use CakeDC\Api\Test\ConfigTrait;
use CakeDC\Api\TestSuite\TestCase;

/**
 * Unit tests for the service event dispatch and error-code mapping.
 */
class ServiceEventsTest extends TestCase
{
    use ConfigTrait;

    protected function tearDown(): void
    {
        ServiceRegistry::getServiceLocator()->clear();
        parent::tearDown();
    }

    protected function buildService(string $serviceName = 'articles', string $baseUrl = '/articles', array $params = [], string $method = 'GET'): \CakeDC\Api\Service\Service
    {
        ServiceRegistry::getServiceLocator()->clear();
        $this->_initializeRequest([
            'params' => ['service' => $serviceName] + $params,
        ], $method);

        return ServiceRegistry::getServiceLocator()->get($serviceName, [
            'version' => null,
            'service' => $serviceName,
            'request' => $this->request,
            'response' => $this->response,
            'baseUrl' => $baseUrl,
        ]);
    }

    public function testBeforeDispatchListenerStopsWithResult(): void
    {
        $service = $this->buildService();
        $service->getEventManager()->on('Service.beforeDispatch', function ($event): void {
            $event->setResult(new Result(['stopped' => true], 200));
        });
        $result = $service->dispatchPrepareAction();
        $this->assertInstanceOf(Result::class, $result);
        $this->assertSame(['stopped' => true], $result->getData());
        $this->assertSame(200, $result->getCode());
    }

    public function testAfterDispatchListenerExceptionPropagates(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('after-dispatch boom');
        $service = $this->buildService();
        $service->dispatchPrepareAction();
        $service->getEventManager()->on('Service.afterDispatch', function (): void {
            throw new \RuntimeException('after-dispatch boom');
        });
        $service->dispatchProcessAction($this->request);
    }

    public function testDispatchRecordNotFoundSets404(): void
    {
        $service = $this->buildService('articles', '/articles/999', ['pass' => ['999']]);
        $result = $service->dispatch();
        $this->assertInstanceOf(Result::class, $result);
        $this->assertSame(404, $result->getCode());
        $this->assertInstanceOf(\Cake\Datasource\Exception\RecordNotFoundException::class, $result->getException());
    }

    public function testDispatchValidationSets422(): void
    {
        $service = $this->buildService('posts', '/posts', [], 'POST');
        $service->setRequest($this->request->withData('body', 'missing title'));

        $result = $service->dispatch();
        $this->assertInstanceOf(Result::class, $result);
        $this->assertSame(422, $result->getCode());
        $this->assertInstanceOf(\CakeDC\Api\Exception\ValidationException::class, $result->getException());
    }
}
