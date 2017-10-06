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

use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Mailer\Email;
use Cake\Utility\Security;

$findRoot = function () {
    $root = dirname(__DIR__);
    if (is_dir($root . '/vendor/cakephp/cakephp')) {
        return $root;
    }

    $root = dirname(dirname(__DIR__));
    if (is_dir($root . '/vendor/cakephp/cakephp')) {
        return $root;
    }

    $root = dirname(dirname(dirname(__DIR__)));
    if (is_dir($root . '/vendor/cakephp/cakephp')) {
        return $root;
    }

    return null;
};

function def($name, $value)
{
    if (!defined($name)) {
        define($name, $value);
    }
}

def('DS', DIRECTORY_SEPARATOR);
def('ROOT', $findRoot());
def('APP_DIR', 'App');
def('WEBROOT_DIR', 'webroot');
def('APP', ROOT . '/tests/App/');
def('CONFIG', ROOT . '/tests/Config/');
def('WWW_ROOT', ROOT . DS . WEBROOT_DIR . DS);
def('TESTS', ROOT . DS . 'tests' . DS);
def('TMP', ROOT . DS . 'tmp' . DS);
def('LOGS', TMP . 'logs' . DS);
def('CACHE', TMP . 'cache' . DS);
def('CAKE_CORE_INCLUDE_PATH', ROOT . '/vendor/cakephp/cakephp');
def('CORE_PATH', CAKE_CORE_INCLUDE_PATH . DS);
def('CAKE', CORE_PATH . 'src' . DS);

require ROOT . '/vendor/cakephp/cakephp/src/basics.php';
require ROOT . '/vendor/autoload.php';

Cake\Core\Configure::write('App.namespace', 'CakeDC\Api\Test\App');
Cake\Core\Configure::write('App.encoding', 'UTF-8');
Cake\Core\Configure::write('debug', true);

$TMP = new \Cake\Filesystem\Folder(TMP);
$TMP->create(TMP . 'cache/models', 0777);
$TMP->create(TMP . 'cache/persistent', 0777);
$TMP->create(TMP . 'cache/views', 0777);

$cache = [
    'default' => [
        'engine' => 'File',
    ],
    '_cake_core_' => [
        'className' => 'File',
        'prefix' => 'api_app_cake_core_',
        'path' => CACHE . 'persistent/',
        'serialize' => true,
        'duration' => '+10 seconds',
    ],
    '_cake_model_' => [
        'className' => 'File',
        'prefix' => 'api_app_cake_model_',
        'path' => CACHE . 'models/',
        'serialize' => 'File',
        'duration' => '+10 seconds',
    ],
];

Cake\Cache\Cache::setConfig($cache);
Cake\Core\Configure::write('EmailTransport', [
    'default' => [
        'className' => 'Debug',
        'host' => 'localhost',
        'port' => 25,
        'timeout' => 30,
        'username' => 'user',
        'password' => 'secret',
        'client' => null,
        'tls' => null,
        'log' => true
    ],
]);
Cake\Core\Configure::write('Email', [
    'default' => [
        'transport' => 'default',
        'from' => 'test@localhost',
        'log' => true,
    ],
]);
Cake\Core\Configure::write('Session', [
    'defaults' => 'php'
]);
Cake\Core\Configure::write('Security.salt', 'bc8b5b70eb0e18bac40204dc3a5b9fbc8b5b70eb0e18bac40204dc3a5b9f');

mb_internal_encoding(Configure::read('App.encoding'));
Security::salt(Configure::read('Security.salt'));
Email::setConfigTransport(Configure::consume('EmailTransport'));
Email::setConfig(Configure::consume('Email'));

Cake\Core\Plugin::load('CakeDC/Api', [
    'path' => ROOT . DS,
    'autoload' => true,
    'bootstrap' => true,
]);

Cake\Routing\DispatcherFactory::add('Routing');
Cake\Routing\DispatcherFactory::add('ControllerFactory');

// Ensure default test connection is defined
if (!getenv('db_dsn')) {
    putenv('db_dsn=sqlite:///:memory:');
}

Cake\Datasource\ConnectionManager::setConfig('test', [
    'url' => getenv('db_dsn'),
    'timezone' => 'UTC'
]);

class_alias('CakeDC\Api\Test\App\Controller\AppController', 'App\Controller\AppController');
