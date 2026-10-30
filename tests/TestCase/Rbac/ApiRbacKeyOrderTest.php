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

namespace CakeDC\Api\Test\TestCase\Rbac;

use Cake\Cache\Cache;
use Cake\Http\ServerRequest;
use Cake\Log\Log;
use CakeDC\Api\Rbac\ApiRbac;
use CakeDC\Api\Rbac\CachedApiRbac;
use CakeDC\Api\Service\Action\CrudAction;
use CakeDC\Api\Service\CrudService;
use CakeDC\Api\TestSuite\TestCase;

/**
 * The `allowed` and `bypassAuth` keys end the check of a rule; the other keys must still apply.
 */
class ApiRbacKeyOrderTest extends TestCase
{
    /**
     * @param string $service Service name.
     * @param string $action Action name.
     * @param string $method HTTP method.
     * @return \Cake\Http\ServerRequest
     */
    protected function request(string $service, string $action, string $method = 'GET'): ServerRequest
    {
        $serviceMock = $this->createMock(CrudService::class);
        $actionMock = $this->createMock(CrudAction::class);
        $actionMock->method('getName')->willReturn($action);
        $actionMock->method('getService')->willReturn($serviceMock);
        $serviceMock->method('getAction')->willReturn($actionMock);
        $serviceMock->method('getName')->willReturn($service);

        return (new ServerRequest(['environment' => ['REQUEST_METHOD' => $method]]))
            ->withAttribute('service', $serviceMock);
    }

    /**
     * @param array $rules Permission rules.
     * @param \Cake\Http\ServerRequest $request Request.
     * @return bool
     */
    protected function check(array $rules, ServerRequest $request): bool
    {
        $rbac = new ApiRbac();
        $rbac->setPermissions($rules);

        return $rbac->checkPermissions(['id' => 1, 'role' => 'user'], $request);
    }

    public function testBypassAuthFirstAppliesOnlyToTheNamedService(): void
    {
        $rules = [['bypassAuth' => true, 'role' => '*', 'service' => 'authors', 'action' => '*', 'method' => '*']];
        $this->assertFalse($this->check($rules, $this->request('articles', 'index')));
        $this->assertTrue($this->check($rules, $this->request('authors', 'index')));
    }

    public function testAllowedFirstAppliesOnlyToTheNamedActionAndMethod(): void
    {
        $rules = [['allowed' => true, 'role' => '*', 'service' => 'articles', 'action' => 'view', 'method' => 'GET']];
        $this->assertFalse($this->check($rules, $this->request('articles', 'delete', 'DELETE')));
        $this->assertFalse($this->check($rules, $this->request('articles', 'view', 'POST')));
        $this->assertTrue($this->check($rules, $this->request('articles', 'view')));
    }

    public function testAllowedFirstAppliesOnlyToTheNamedRole(): void
    {
        $rules = [['allowed' => true, 'role' => 'admin', 'service' => '*', 'action' => '*', 'method' => '*']];
        $this->assertFalse($this->check($rules, $this->request('articles', 'index')));
    }

    public function testRulesWithTheKeyLastBehaveAsBefore(): void
    {
        $rules = [['role' => '*', 'service' => 'articles', 'action' => '*', 'method' => '*', 'allowed' => true]];
        $this->assertTrue($this->check($rules, $this->request('articles', 'index')));
        $this->assertFalse($this->check($rules, $this->request('authors', 'index')));
    }

    public function testTargetAttributeIsCheckedInsteadOfTheAction(): void
    {
        $rules = [['role' => '*', 'service' => 'authors', 'action' => 'index', 'method' => '*', 'allowed' => true]];
        $request = $this->request('articles', 'view')
            ->withAttribute(ApiRbac::TARGET_ATTRIBUTE, ['service' => ['authors'], 'action' => 'index']);
        $this->assertTrue($this->check($rules, $request));
        $this->assertFalse($this->check($rules, $this->request('articles', 'view')));
    }

    public function testBypassAuthFalseDoesNotAllowAnonymousUsers(): void
    {
        $rbac = new ApiRbac();
        $rbac->setPermissions([['role' => '*', 'service' => '*', 'action' => '*', 'method' => '*', 'bypassAuth' => false]]);
        $this->assertFalse($rbac->checkPermissions([], $this->request('articles', 'index')));
        $this->assertTrue($rbac->checkPermissions(['id' => 1, 'role' => 'user'], $this->request('articles', 'index')));
    }

    public function testInvertedAllowedFirstAppliesOnlyToTheNamedService(): void
    {
        $rules = [['*allowed' => false, 'role' => '*', 'service' => 'authors', 'action' => '*', 'method' => '*']];
        $this->assertFalse($this->check($rules, $this->request('articles', 'index')));
        $this->assertTrue($this->check($rules, $this->request('authors', 'index')));
    }

    public function testCachedRbacChecksTheTarget(): void
    {
        if (!class_exists(CachedApiRbac::class)) {
            $this->markTestSkipped('No CachedApiRbac on this line');
        }
        // CachedApiRbac keeps its rule map in this cache config
        if (!Cache::getConfig('_cakedc_api_auth_')) {
            Cache::setConfig('_cakedc_api_auth_', ['className' => 'Array']);
        }
        Cache::clear('_cakedc_api_auth_');
        $rules = [['role' => '*', 'service' => 'authors', 'action' => 'index', 'method' => '*', 'allowed' => true]];
        $rbac = new CachedApiRbac(['api_permissions' => $rules]);
        Cache::clear('_cakedc_api_auth_');
        $request = $this->request('articles', 'view')
            ->withAttribute(ApiRbac::TARGET_ATTRIBUTE, ['service' => ['authors'], 'action' => 'index']);
        $this->assertTrue($rbac->checkPermissions(['id' => 1, 'role' => 'user'], $request));
        $this->assertFalse($rbac->checkPermissions(['id' => 1, 'role' => 'user'], $this->request('articles', 'view')));
    }

    /**
     * Rule lists where the order of rules for different roles or services decides the result.
     *
     * @return array
     */
    public static function orderedRulesProvider(): array
    {
        $deny = ['action' => '*', 'method' => '*', 'allowed' => false];
        $allow = ['action' => '*', 'method' => '*', 'allowed' => true];

        return [
            'any role denied before own role allowed' => [[
                ['role' => '*', 'service' => 'articles'] + $deny,
                ['role' => 'user', 'service' => 'articles'] + $allow,
            ], false],
            'own role allowed before any role denied' => [[
                ['role' => 'user', 'service' => 'articles'] + $allow,
                ['role' => '*', 'service' => 'articles'] + $deny,
            ], true],
            'any service denied before own service allowed' => [[
                ['role' => 'user', 'service' => '*'] + $deny,
                ['role' => 'user', 'service' => 'articles'] + $allow,
            ], false],
            'role list denied before own role allowed' => [[
                ['role' => ['admin', 'user'], 'service' => ['authors', 'articles']] + $deny,
                ['role' => 'user', 'service' => 'articles'] + $allow,
            ], false],
            'camelized service name allowed' => [[
                ['role' => 'user', 'service' => 'Articles'] + $allow,
            ], true],
            'string keys keep their order' => [[
                'z_deny' => ['role' => '*', 'service' => '*'] + $deny,
                'a_allow' => ['role' => '*', 'service' => '*'] + $allow,
            ], false],
            'unordered keys keep their order' => [[
                5 => ['role' => '*', 'service' => '*'] + $deny,
                1 => ['role' => '*', 'service' => '*'] + $allow,
            ], false],
            'rule without an action refuses every role' => [[
                ['role' => 'admin', 'service' => 'articles', 'allowed' => true],
                ['role' => '*', 'service' => '*'] + $allow,
            ], false],
            'function name role is checked' => [[
                ['role' => __NAMESPACE__ . '\\cachedRbacRuleFunction', 'service' => 'articles'] + $deny,
                ['role' => '*', 'service' => '*'] + $allow,
            ], false],
            'static method role is checked' => [[
                ['role' => [self::class, 'callableRule'], 'service' => 'articles'] + $deny,
                ['role' => '*', 'service' => '*'] + $allow,
            ], false],
            'Class::method role is checked' => [[
                ['role' => self::class . '::callableRule', 'service' => 'articles'] + $deny,
                ['role' => '*', 'service' => '*'] + $allow,
            ], false],
            'static method service is checked' => [[
                ['role' => 'user', 'service' => [self::class, 'callableRule']] + $deny,
                ['role' => '*', 'service' => '*'] + $allow,
            ], false],
            'static method allowed is called' => [[
                ['role' => 'user', 'service' => 'articles', 'action' => '*', 'method' => '*',
                    'allowed' => [self::class, 'denyingRule']],
                ['role' => '*', 'service' => '*'] + $allow,
            ], false],
            'Class::method allowed is called' => [[
                ['role' => 'user', 'service' => 'articles', 'action' => '*', 'method' => '*',
                    'allowed' => self::class . '::denyingRule'],
                ['role' => '*', 'service' => '*'] + $allow,
            ], false],
            'rule with a user key refuses every role' => [[
                ['role' => '*', 'service' => '*', 'user' => 'someone'] + $allow,
                ['role' => '*', 'service' => '*'] + $allow,
            ], false],
            'non-string role is checked' => [[
                ['role' => true, 'service' => 'articles'] + $deny,
                ['role' => '*', 'service' => '*'] + $allow,
            ], false],
            'callable role is checked' => [[
                ['role' => function () {
                    return true;
                }, 'service' => 'articles'] + $deny,
                ['role' => '*', 'service' => '*'] + $allow,
            ], false],
        ];
    }

    /**
     * CachedApiRbac decides by the first matching rule, like ApiRbac.
     *
     * @dataProvider orderedRulesProvider
     * @param array $rules Permission rules.
     * @param bool $expected Expected decision.
     * @return void
     */
    public function testCachedRbacFollowsTheRuleOrder(array $rules, bool $expected): void
    {
        $request = $this->request('articles', 'index');

        $this->assertSame($expected, $this->check($rules, $request));
        $this->assertSame($expected, $this->cached($rules)->checkPermissions(['id' => 1, 'role' => 'user'], $request));
    }

    /**
     * On a nested route the rules of the inner service apply, as with ApiRbac.
     *
     * @return void
     */
    public function testCachedRbacChecksTheActionServiceOnNestedRoutes(): void
    {
        $inner = $this->createMock(CrudService::class);
        $inner->method('getName')->willReturn('articles');
        $action = $this->createMock(CrudAction::class);
        $action->method('getName')->willReturn('index');
        $action->method('getService')->willReturn($inner);
        $outer = $this->createMock(CrudService::class);
        $outer->method('getName')->willReturn('authors');
        $outer->method('getAction')->willReturn($action);
        $request = (new ServerRequest(['environment' => ['REQUEST_METHOD' => 'GET']]))->withAttribute('service', $outer);
        $deny = ['action' => '*', 'method' => '*', 'allowed' => false];
        $allow = ['action' => '*', 'method' => '*', 'allowed' => true];

        $cases = [
            [[['role' => 'user', 'service' => 'articles'] + $deny, ['role' => '*', 'service' => '*'] + $allow], false],
            [[['role' => 'user', 'service' => 'articles'] + $allow], true],
        ];
        foreach ($cases as [$rules, $expected]) {
            $this->assertSame($expected, $this->check($rules, $request));
            $this->assertSame($expected, $this->cached($rules)->checkPermissions(['id' => 1, 'role' => 'user'], $request));
        }
    }

    /**
     * A callable for a rule's role or service.
     *
     * @return bool
     */
    public static function callableRule(): bool
    {
        return true;
    }

    /**
     * A callable for a rule's `allowed` key that denies.
     *
     * @return bool
     */
    public static function denyingRule(): bool
    {
        return false;
    }

    /**
     * A user role that is not a plain name is matched as ApiRbac matches it.
     *
     * @return void
     */
    public function testCachedRbacMatchesARoleList(): void
    {
        $rules = [['role' => ['admin', 'user'], 'service' => '*', 'action' => '*', 'method' => '*', 'allowed' => true]];
        $user = ['id' => 1, 'role' => ['admin', 'user']];
        $request = $this->request('articles', 'index');
        $rbac = new ApiRbac();
        $rbac->setPermissions($rules);

        $this->assertTrue($rbac->checkPermissions($user, $request));
        $this->assertTrue($this->cached($rules)->checkPermissions($user, $request));
    }

    /**
     * A role written in another form, such as `SuperAdmin`, is matched as ApiRbac matches it.
     *
     * @return void
     */
    public function testCachedRbacMatchesACamelizedRole(): void
    {
        $rules = [['role' => 'SuperAdmin', 'service' => '*', 'action' => '*', 'method' => '*', 'allowed' => true]];
        $user = ['id' => 1, 'role' => 'super_admin'];
        $request = $this->request('articles', 'index');
        $rbac = new ApiRbac();
        $rbac->setPermissions($rules);

        $this->assertTrue($rbac->checkPermissions($user, $request));
        $this->assertTrue($this->cached($rules)->checkPermissions($user, $request));
    }

    /**
     * A changed rule list takes effect without clearing the cache.
     *
     * @return void
     */
    public function testCachedRbacUsesAChangedRuleList(): void
    {
        $rule = ['role' => '*', 'service' => '*', 'action' => '*', 'method' => '*'];
        $this->warmCache([$rule + ['allowed' => true]]);
        $rbac = new CachedApiRbac(['api_permissions' => [$rule + ['allowed' => false]]]);
        Cache::clear('_cakedc_api_auth_');

        $this->assertFalse($rbac->checkPermissions(['id' => 1, 'role' => 'user'], $this->request('articles', 'index')));
    }

    /**
     * A rule that ApiRbac refuses for every request is logged when the map is built.
     *
     * @return void
     */
    public function testCachedRbacLogsARefusedRule(): void
    {
        Log::setConfig('cached_rbac_test', ['className' => 'Array', 'levels' => ['warning']]);
        try {
            $this->cached([
                ['role' => '*', 'service' => '*', 'action' => '*', 'method' => '*', 'allowed' => true],
                ['role' => 'admin', 'service' => 'reports', 'allowed' => true],
            ]);
            $logged = implode("\n", Log::engine('cached_rbac_test')->read());
        } finally {
            Log::drop('cached_rbac_test');
        }

        $this->assertStringContainsString('Permission rule 2 has no `service` or `action`', $logged);
    }

    /**
     * Rules for a target are checked in their order.
     *
     * @return void
     */
    public function testCachedRbacFollowsTheRuleOrderForATarget(): void
    {
        $rules = [
            ['role' => '*', 'service' => 'authors', 'action' => '*', 'method' => '*', 'allowed' => false],
            ['role' => 'user', 'service' => 'authors', 'action' => '*', 'method' => '*', 'allowed' => true],
        ];
        $request = $this->request('articles', 'view')
            ->withAttribute(ApiRbac::TARGET_ATTRIBUTE, ['service' => ['authors', 'Authors'], 'action' => 'index']);

        $this->assertFalse($this->check($rules, $request));
        $this->assertFalse($this->cached($rules)->checkPermissions(['id' => 1, 'role' => 'user'], $request));
    }

    /**
     * A map cached by an earlier version is not used.
     *
     * @return void
     */
    public function testCachedRbacIgnoresMapsOfEarlierVersions(): void
    {
        if (!Cache::getConfig('_cakedc_api_auth_')) {
            Cache::setConfig('_cakedc_api_auth_', ['className' => 'Array']);
        }
        Cache::clear('_cakedc_api_auth_');
        $allowAll = ['role' => '*', 'service' => '*', 'action' => '*', 'method' => '*', 'allowed' => true];
        Cache::write('api_permissions_map', ['*' => ['*' => [$allowAll]]], '_cakedc_api_auth_');
        $rbac = new CachedApiRbac(['api_permissions' => []]);
        Cache::clear('_cakedc_api_auth_');

        $this->assertFalse($rbac->checkPermissions(['id' => 1, 'role' => 'user'], $this->request('articles', 'index')));
    }

    /**
     * A changed option of a Rule object takes effect without clearing a cache that serializes.
     *
     * @return void
     */
    public function testCachedRbacUsesChangedRuleOptions(): void
    {
        $rule = ['role' => '*', 'service' => '*', 'action' => '*', 'method' => '*'];
        $this->withFileCache(function () use ($rule) {
            new CachedApiRbac(['api_permissions' => [$rule + ['allowed' => new OptionRule(['allow' => true])]]]);
            $rbac = new CachedApiRbac(['api_permissions' => [$rule + ['allowed' => new OptionRule(['allow' => false])]]]);

            $this->assertFalse(
                $rbac->checkPermissions(['id' => 1, 'role' => 'user'], $this->request('articles', 'index'))
            );
        });
    }

    /**
     * Rules with closures work with a cache that serializes, because the map holds rule positions only.
     *
     * @return void
     */
    public function testCachedRbacWorksWithClosuresAndAFileCache(): void
    {
        $rules = [
            ['role' => '*', 'service' => '*', 'action' => '*', 'method' => '*', 'allowed' => function () {
                return true;
            }],
        ];
        $this->withFileCache(function () use ($rules) {
            new CachedApiRbac(['api_permissions' => $rules]);
            $rbac = new CachedApiRbac(['api_permissions' => $rules]);

            $this->assertTrue(
                $rbac->checkPermissions(['id' => 1, 'role' => 'user'], $this->request('articles', 'index'))
            );
        });
    }

    /**
     * Rule lists with names that are not valid UTF-8 get their own cache keys.
     *
     * @return void
     */
    public function testCachedRbacCacheKeyOfNamesThatAreNotUtf8(): void
    {
        $rbac = new class extends CachedApiRbac {
            /**
             * Without the parent constructor, no map is built.
             */
            public function __construct()
            {
            }

            /**
             * @param array $permissions Permission rules.
             * @return string
             */
            public static function key(array $permissions): string
            {
                return static::_cacheKey($permissions);
            }
        };
        $rule = ['service' => '*', 'action' => '*', 'allowed' => true];

        $keys = [
            $rbac::key([['role' => "\xff"] + $rule]),
            $rbac::key([['role' => "\xfe"] + $rule]),
            $rbac::key([]),
        ];
        $this->assertCount(3, array_unique($keys));
    }

    /**
     * Rules set with setPermissions() are checked instead of the rules of the constructor.
     *
     * @return void
     */
    public function testCachedRbacUsesSetPermissions(): void
    {
        $rule = ['role' => '*', 'service' => '*', 'action' => '*', 'method' => '*'];
        $rbac = $this->cached([$rule + ['allowed' => true]]);
        $rbac->setPermissions([$rule + ['allowed' => false]]);
        Cache::clear('_cakedc_api_auth_');

        $this->assertFalse($rbac->checkPermissions(['id' => 1, 'role' => 'user'], $this->request('articles', 'index')));
    }

    /**
     * Runs a test with a File cache that serializes as the `_cakedc_api_auth_` engine.
     *
     * @param callable $test Test.
     * @return void
     */
    protected function withFileCache(callable $test): void
    {
        $previous = Cache::getConfig('_cakedc_api_auth_');
        Cache::drop('_cakedc_api_auth_');
        Cache::setConfig('_cakedc_api_auth_', [
            'className' => 'File',
            'path' => TMP . 'cache' . DS . 'cached_rbac_test' . DS,
            'serialize' => true,
        ]);
        try {
            Cache::clear('_cakedc_api_auth_');
            $test();
        } finally {
            Cache::clear('_cakedc_api_auth_');
            Cache::drop('_cakedc_api_auth_');
            if ($previous) {
                Cache::setConfig('_cakedc_api_auth_', $previous);
            }
        }
    }

    /**
     * Builds and caches the map of the rules, and leaves it in the cache.
     *
     * @param array $rules Permission rules.
     * @return void
     */
    protected function warmCache(array $rules): void
    {
        if (!Cache::getConfig('_cakedc_api_auth_')) {
            Cache::setConfig('_cakedc_api_auth_', ['className' => 'Array']);
        }
        Cache::clear('_cakedc_api_auth_');
        new CachedApiRbac(['api_permissions' => $rules]);
    }

    /**
     * @param array $rules Permission rules.
     * @return \CakeDC\Api\Rbac\CachedApiRbac
     */
    protected function cached(array $rules): CachedApiRbac
    {
        if (!Cache::getConfig('_cakedc_api_auth_')) {
            Cache::setConfig('_cakedc_api_auth_', ['className' => 'Array']);
        }
        Cache::clear('_cakedc_api_auth_');
        $rbac = new CachedApiRbac(['api_permissions' => $rules]);
        Cache::clear('_cakedc_api_auth_');

        return $rbac;
    }
}

/**
 * A function used as a rule's role.
 *
 * @return bool
 */
function cachedRbacRuleFunction(): bool
{
    return true;
}
