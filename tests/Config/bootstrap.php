<?php
/**
 * Copyright 2016 - 2017, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2016 - 2017, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

// Plugin::load('CakeDC/Api', ['bootstrap' => false, 'routes' => true]);

use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Utility\Security;

Configure::load('api');
Configure::write('Users.config', ['users']);

Cake\Core\Plugin::unload('CakeDC/Users');
Cake\Core\Plugin::load('CakeDC/Users', [
    'path' => ROOT . DS . 'vendor' . DS . 'cakedc' . DS . 'users' . DS,
    'autoload' => true,
    'bootstrap' => true,
    'routes' => true,
]);

Cake\Core\Configure::write('Security.salt', 'bc8b5b70eb0e18bac40204dc3a5b9fbc8b5b70eb0e18bac40204dc3a5b9f');
Security::salt(Configure::read('Security.salt'));
