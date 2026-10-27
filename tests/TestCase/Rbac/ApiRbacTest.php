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

use CakeDC\Api\Rbac\ApiRbac;
use CakeDC\Api\Service\ServiceRegistry;
use CakeDC\Api\Test\ConfigTrait;
use CakeDC\Api\TestSuite\TestCase;

/**
 * Unit tests for the RBAC permission matching logic.
 */
class ApiRbacTest extends TestCase
{
    use ConfigTrait;

    /**
     * tearDown
     *
     * @return void
     */
    public function tearDown(): void
    {
        ServiceRegistry::getServiceLocator()->clear();
        parent::tearDown();
    }

    /**
     * Build a service with a resolved action and attach it to the request.
     *
     * @param array $permissions RBAC permissions.
     * @param array $user User data.
     * @return array{rbac: \CakeDC\Api\Rbac\ApiRbac, request: \Cake\Http\ServerRequest, user: array}
     */
    protected function buildRequest(array $permissions, array $user = ['role' => 1, 'id' => 1]): array
    {
        ServiceRegistry::getServiceLocator()->clear();
        $this->_initializeRequest([
            'params' => ['service' => 'articles'],
        ], 'GET');
        $service = ServiceRegistry::getServiceLocator()->get('articles', [
            'version' => null,
            'service' => 'articles',
            'request' => $this->request,
            'response' => $this->response,
            'baseUrl' => '/articles',
        ]);
        $service->dispatchPrepareAction();

        $this->request = $this->request->withAttribute('service', $service);

        $rbac = new ApiRbac([
            'api_permissions' => $permissions,
            'role_field' => 'role',
            'default_role' => 'user',
        ]);

        return [$rbac, $this->request, $user];
    }

    /**
     * Apps commonly store the role as an integer group id. The role is passed to
     * the rules (typed `string`), so an integer role must be cast to string and
     * must not raise a TypeError.
     */
    public function testIntegerRoleIsPassedAsStringToRules(): void
    {
        $rule = new RoleCapturingRule();
        [$rbac, $request, $user] = $this->buildRequest([
            ['role' => 1, 'service' => 'articles', 'action' => 'index', 'rule' => $rule],
        ]);

        $this->assertTrue($rbac->checkPermissions($user, $request));
        $this->assertTrue($rule->called);
        $this->assertSame('1', $rule->receivedRole);
    }
}
