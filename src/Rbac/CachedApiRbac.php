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
     * A map of rules
     *
     * @var array[] rules array
     */
    protected array $permissionsMap = [];

    /**
     * CachedApiRbac constructor.
     *
     * @param array $config Class configuration
     */
    public function __construct(array $config = [])
    {
        parent::__construct($config);
        $this->permissionsMap = Cache::remember('api_permissions_map', fn(): array => $this->buildPermissionsMap(), '_cakedc_api_auth_');
    }

    /**
     * Build permissions map for caching
     *
     * @return array
     */
    public function buildPermissionsMap(): array
    {
        $asArray = function (array $permission, $key, $default = null): array {
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
        foreach ($this->permissions as $permission) {
            $role = $permission['role'] ?? '*';
            $roles = (array)$role;
            foreach ($roles as $role) {
                $services = $asArray($permission, 'service', '*');
                foreach ($services as $service) {
                    $key = $service;
                    $map[$role][$key][] = $permission;
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
    #[\Override]
    public function checkPermissions(array|\ArrayAccess $user, ServerRequestInterface $request): bool
    {
        $roleField = $this->getConfig('role_field');
        $defaultRole = $this->getConfig('default_role');
        $role = Hash::get($user, $roleField, $defaultRole);

        $service = $request->getAttribute('service');
        if ($service === null) {
            return false;
        }

        $serviceName = $service->getName();
        $keys = [$serviceName, '*'];

        foreach ([$role, '*'] as $checkRole) {
            if (!array_key_exists($checkRole, $this->permissionsMap)) {
                continue;
            }
            foreach ($keys as $key) {
                if (!array_key_exists($key, $this->permissionsMap[$checkRole])) {
                    continue;
                }
                $permissions = $this->permissionsMap[$checkRole][$key];
                foreach ($permissions as $permission) {
                    $matchResult = $this->matchPermission($permission, $user, $role, $request);
                    if ($matchResult instanceof \CakeDC\Auth\Rbac\PermissionMatchResult) {
                        if ($this->getConfig('log')) {
                            $this->log($matchResult->getReason(), LogLevel::DEBUG);
                        }

                        return $matchResult->isAllowed();
                    }
                }
            }
        }

        return false;
    }
}
