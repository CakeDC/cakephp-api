<?php
/**
 * Copyright 2016 - 2019, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2016 - 2019, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

use Cake\Core\Configure;
use Cake\Utility\Security;

Cake\Core\Configure::write('Security.salt', 'bc8b5b70eb0e18bac40204dc3a5b9fbc8b5b70eb0e18bac40204dc3a5b9f');
Security::setSalt(Configure::read('Security.salt'));
