<?php
declare(strict_types=1);

use Cake\Core\Configure;

$permissions = [
    [
        'role' => '*',
        'service' => '*',
        'action' => '*',
        'method' => '*',
        'bypassAuth' => true,
    ],
    [
        'role' => '*',
        'service' => 'Auth',
        'action' => 'login',
        'method' => '*',
        'bypassAuth' => true,
    ],
];

// tests can replace the permission list
$override = Configure::read('Test.Api.Permissions');
if (is_array($override)) {
    $permissions = $override;
}

return [
    'CakeDC/Auth.api_permissions' => $permissions,
];
