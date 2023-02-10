<?php

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

return [
    'CakeDC/Auth.api_permissions' => $permissions,
];
