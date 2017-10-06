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
use Cake\Log\Log;

Configure::load('CakeDC/Api.api');
collection((array)Configure::read('Api.config'))->each(function ($file) {
    Configure::load($file);
});

if (!Log::engine('api')) {
    Log::setConfig('api', [
        'className' => Configure::read('Api.Log.className'),
        'path' => LOGS,
        'scopes' => Configure::read('Api.Log.scopes'),
        'levels' => Configure::read('Api.Log.levels'),
        'file' => Configure::read('Api.Log.file'),
    ]);
}
