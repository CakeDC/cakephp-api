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

use Cake\AttributeResolver\AttributeResolver;
use Cake\Cache\Cache;
use Cake\Cache\Engine\FileEngine;
use Cake\Core\Configure;
use Cake\Log\Log;

Configure::load('CakeDC/Api.api');
collection((array)Configure::read('Api.config'))->each(function ($merge, $file) {
	if (is_int($file)) {
		$file = $merge;
		$merge = true;
	}
    Configure::load($file, 'default', $merge);
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

// Attribute resolver cache. Auto-registered with defaults; the host application
// may override by configuring the `_cakedc_api_attributes_` engine (or its own
// engine via the resolver config) before the plugin loads.
if (!in_array('_cakedc_api_attributes_', Cache::configured(), true)) {
    Cache::setConfig('_cakedc_api_attributes_', [
        'className' => FileEngine::class,
        'prefix' => 'cakedc_api_attributes_',
        'path' => CACHE . 'persistent' . DS,
        'serialize' => true,
        'duration' => '+1 hour',
    ]);
}

// Attribute routing resolver config. Auto-registered so attribute-declared
// service routes work out of the box; override `paths` / `cache` / `validateFiles`
// by configuring the `default` resolver config in the application bootstrap.
if (AttributeResolver::getConfig('default') === null) {
    AttributeResolver::setConfig('default', [
        'paths' => ['src/Service/*.php', 'src/Service/**/*.php'],
        'cache' => '_cakedc_api_attributes_',
        'validateFiles' => true,
    ]);
}
