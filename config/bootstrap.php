<?php
/**
 * Copyright 2016-2017, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2016, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

use Cake\Core\Configure;

Configure::load('CakeDC/Api.api');
collection((array)Configure::read('Api.config'))->each(function ($file) {
    Configure::load($file);
});

Log::setConfig('api', [
    'className' => 'File',
    'path' => LOGS,
    'scopes' => ['api'],
    'levels' => ['error', 'info'],
    'file' => 'api.log',
])