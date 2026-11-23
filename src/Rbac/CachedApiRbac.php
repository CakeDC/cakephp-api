<?php
declare(strict_types=1);

/**
 * Copyright 2010 - 2025, Cake Development Corporation (https://www.cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2010 - 2025, Cake Development Corporation (https://www.cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Api\Rbac;

use Cake\Cache\Cache;
use Cake\Utility\Hash;
use CakeDC\Api\Rbac\ApiRbac;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LogLevel;

/**
 * Class CachedApiRbac
 *
 * Cached version of ApiRbac for improved performance.
 * Builds a permissions map based on service/action and caches it.
 */
class CachedApiRbac extends ApiRbac
{
    /**
     * Prefix of the cache key of the permissions map. The key also depends on the rules, so a changed
     * rule list builds a new map, and maps cached by earlier versions are not read.
     */
    public const CACHE_KEY = 'api_permissions_map_ordered';

    /**
     * Positions of the rules in the permissions list, by role and service
     *
     * @var array<string, array<string, array<int, int>>>
     */
    protected $permissionsMap = [];

    /**
     * CachedApiRbac constructor.
     *
     * @param array $config Class configuration
     */
    public function __construct($config = [])
    {
        parent::__construct($config);
        $this->_loadPermissionsMap();
    }

    /**
     * @param array $permissions permissions
     * @return void
     */
    public function setPermissions($permissions)
    {
        parent::setPermissions($permissions);
        $this->_loadPermissionsMap();
    }

    /**
     * Read the permissions map of the current rules from the cache, or build and cache it.
     *
     * @return void
     */
    protected function _loadPermissionsMap(): void
    {
        $key = static::_cacheKey((array)$this->permissions);
        $this->permissionsMap = Cache::remember($key, function () {
            return $this->buildPermissionsMap();
        }, '_cakedc_api_auth_');
    }

    /**
     * Cache key of the permissions map of a rule list.
     *
     * The map holds rule positions only, and an object in a rule (a Rule or a closure) only decides
     * where the rule is stored by its class, so the key uses the class of each object.
     *
     * @param array $permissions Permission rules.
     * @return string
     */
    protected static function _cacheKey(array $permissions): string
    {
        return static::CACHE_KEY . '_' . md5(serialize(static::_keyData(array_values($permissions))));
    }

    /**
     * Rule data for the cache key: objects are replaced by their class name.
     *
     * @param mixed $value Rule list, rule or rule value.
     * @return mixed
     */
    protected static function _keyData($value)
    {
        if (is_object($value)) {
            return ['__object' => get_class($value)];
        }
        if (is_array($value)) {
            return array_map(function ($item) {
                return static::_keyData($item);
            }, $value);
        }

        return $value;
    }

    /**
     * Build permissions map for caching
     *
     * The map holds the position of each rule in the permissions list, not the rule, so the rules are
     * always read from the current list.
     *
     * @return array<string, array<string, array<int, int>>>
     */
    public function buildPermissionsMap()
    {
        $asArray = function ($permission, $key, $default = null) {
            if ($default !== null && !array_key_exists($key, $permission)) {
                return [$default, '_'];
            }
            if (!array_key_exists($key, $permission) || $permission[$key] == false || $permission[$key] == null) {
                return ['_'];
            }
            $item = $permission[$key];
            if (is_string($item)) {
                return [$item];
            }
            return (array)$item;
        };

        $map = [];
        // positions, not keys: a list with string or unordered keys is checked in its own order
        foreach (array_values((array)$this->permissions) as $index => $permission) {
            if ($this->_isRefused($permission)) {
                $this->log(sprintf(
                    'Permission rule %d has no `service` or `action`, or has a `user` key: '
                    . 'it denies every request that reaches it',
                    $index + 1
                ), LogLevel::WARNING);
            }
            if (!$this->_isIndexable($permission)) {
                // ApiRbac evaluates such a rule for every request, so every request has to find it
                $map['*']['_'][$index] = $index;
                continue;
            }
            $roles = isset($permission['role']) ? (array)$permission['role'] : ['*'];
            foreach ($roles as $role) {
                $services = $asArray($permission, 'service', '*');
                foreach ($services as $service) {
                    $map[$role][$service][$index] = $index;
                }
            }
        }

        return $map;
    }

    /**
     * Match against permissions using cached map
     *
     * @param array|\ArrayAccess $user current user array
     * @param \Psr\Http\Message\ServerRequestInterface $request request
     * @return bool true if there is a match in permissions
     */
    public function checkPermissions($user, ServerRequestInterface $request)
    {
        $roleField = $this->getConfig('role_field');
        $defaultRole = $this->getConfig('default_role');
        $role = Hash::get($user, $roleField, $defaultRole);

        $service = $request->getAttribute('service');
        if ($service === null) {
            return false;
        }

        $target = (array)$request->getAttribute(self::TARGET_ATTRIBUTE);
        $candidates = $target['service'] ?? null;
        if ($candidates === null) {
            // ApiRbac checks the service of the action, which is the inner service on nested routes
            $candidates = [$service->getName()];
            $action = $service->getAction();
            if ($action !== null) {
                $candidates[] = $action->getService()->getName();
            }
        }
        $candidates = (array)$candidates;

        // the map only narrows down the rules; each one is still matched, in the order of the
        // permissions list, so the first matching rule decides as in ApiRbac
        $rules = [];
        // a role that is not a plain name, such as a list of roles, is left to the rule matching
        $anyRole = !is_string($role) && !is_int($role);
        foreach ($this->permissionsMap as $mapRole => $services) {
            if (!$anyRole && !$this->_matchOrAsterisk((string)$mapRole, $role, true)) {
                continue;
            }
            foreach ($services as $mapService => $serviceRules) {
                if ($this->_matchesAnyService((string)$mapService, $candidates)) {
                    $rules += $serviceRules;
                }
            }
        }
        ksort($rules);

        $permissions = array_values((array)$this->permissions);
        foreach ($rules as $index) {
            if (!isset($permissions[$index])) {
                continue;
            }
            $permission = $permissions[$index];
            $matchResult = $this->_matchPermission($permission, $user, $role, $request);
            if ($matchResult !== null) {
                if ($this->getConfig('log')) {
                    $this->log($matchResult->getReason(), LogLevel::DEBUG);
                }

                return $matchResult->isAllowed();
            }
        }

        return false;
    }

    /**
     * Whether a rule can be stored under its role and service keys.
     *
     * Rules that ApiRbac refuses for every request (no `service` or `action`, or a `user` key), and
     * rules whose role or service is not a plain name, such as a callable, are not.
     *
     * @param array $permission Permission rule.
     * @return bool
     */
    protected function _isIndexable(array $permission): bool
    {
        if ($this->_isRefused($permission)) {
            return false;
        }
        foreach (['role', 'service'] as $key) {
            if (!isset($permission[$key])) {
                continue;
            }
            $value = $permission[$key];
            // a `className` array is a Rule, which ApiRbac evaluates for every request
            if (is_array($value) && isset($value['className'])) {
                return false;
            }
            // so is a callable, including a function name such as `'file'`
            if (is_callable($value)) {
                return false;
            }
            foreach (is_array($value) ? $value : [$value] as $name) {
                if (!is_string($name) && !is_int($name)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Whether ApiRbac refuses the rule for every request: it has no `service` or `action`, or it has a
     * `user` key.
     *
     * @param array $permission Permission rule.
     * @return bool
     */
    protected function _isRefused(array $permission): bool
    {
        $issetService = isset($permission['service']) || isset($permission['*service']);
        $issetAction = isset($permission['action']) || isset($permission['*action']);

        return !$issetService || !$issetAction || isset($permission['user']) || isset($permission['*user']);
    }

    /**
     * Whether rules stored under a service key can apply to one of the service names.
     *
     * Uses the same comparison as ApiRbac, so `Auth` matches the `auth` service. Rules with an
     * empty service (key `_`) are kept and left to the rule matching.
     *
     * @param string $mapService Service key of the map.
     * @param array $candidates Service names of the request.
     * @return bool
     */
    protected function _matchesAnyService(string $mapService, array $candidates): bool
    {
        if ($mapService === '_') {
            return true;
        }
        foreach ($candidates as $candidate) {
            if ($this->_matchOrAsterisk($mapService, $candidate, true)) {
                return true;
            }
        }

        return false;
    }
}
