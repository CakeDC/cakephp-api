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

use Cake\Http\Response;
use Cake\Http\ServerRequest;
use CakeDC\Api\Service\Action\CrudIndexAction;
use CakeDC\Api\Service\FallbackService;
use CakeDC\Api\TestSuite\TestCase;

/**
 * Regression tests for the extension loading logic.
 *
 * CakePHP 5 ObjectRegistry::normalizeArray() returns a `name => config` map (the
 * old CakePHP 4 `class`/`config` shape is gone), so extensions must be loaded by
 * their normalized name. A plugin-prefixed extension (e.g. `CakeDC/Api.Log`) only
 * carries a `className` key, and an app extension without a plugin prefix carries
 * neither `class` nor `className`.
 */
class ExtensionLoadingTest extends TestCase
{
    /**
     * A service configured with extensions must load them without a TypeError.
     *
     * @return void
     */
    public function testServiceLoadsConfiguredExtension(): void
    {
        $service = new FallbackService([
            'request' => new ServerRequest(),
            'response' => new Response(),
            'Extension' => ['CakeDC/Api.Log'],
        ]);

        $this->assertTrue($service->getExtensions()->has('Log'));
    }

    /**
     * An action configured with an app extension (no plugin prefix) must load it.
     *
     * @return void
     */
    public function testActionLoadsAppExtensionWithoutPluginPrefix(): void
    {
        $request = new ServerRequest();
        $response = new Response();
        $service = new FallbackService([
            'request' => $request,
            'response' => $response,
        ]);
        $action = new CrudIndexAction([
            'service' => $service,
            'request' => $request,
            'response' => $response,
            'Extension' => ['Sort'],
        ]);

        $this->assertNotNull($action->getExtensions());
        $this->assertTrue($action->getExtensions()->has('Sort'));
    }
}
