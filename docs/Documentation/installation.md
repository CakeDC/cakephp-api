# Installation

## Composer

Installation
You can install this plugin into your CakePHP application using [composer](http://getcomposer.org/doc/00-intro.md).

The recommended way to install composer packages is:

```
composer require cakedc/cakephp-api
```

## Load the Plugin

Ensure the API Plugin is loaded in your config/bootstrap.php file

```php
Plugin::load('CakeDC/Api', ['bootstrap' => true, 'routes' => true]);
```

## Configuration
You can configure the api overwriting the api.php, how?
we need to create an **api.php** file in the *config* folder. You can copy the existing configuration file
under `vendor/cakedc/cakephp-api/config/api.php` and customize it for your application.
Remember to load the new configuration file in `bootstrap.php`

```php
Plugin::load('CakeDC/Api', ['bootstrap' => false, 'routes' => true]);
Configure::load('api');
```

CakePHP 4.x configuration loading form Application class:

```php
Configure::write('Api.config', ['api']);
$this->addPlugin('CakeDC/Api', ['bootstrap' => true, 'routes' => true]);
```
In this case plugin will load default configuration file from CakeDC/Api/config/api.php
and after that append the configuration from ./config/api.php

If needed to overwrite one of sections in the default confugration file it is possible to pass
config names as key and merge param as value of `Api.config` settings.

Example, if we want merge api.php and overwrite specific node like Api.Middleware we can create addtional configuration file config/api_mw.php
```php
return [
    'Api.Middleware' => [
		// ... overwrite section here
	]
];
```
And in Application.php we will have
```php
Configure::write('Api.config', ['api' => true, 'api_mw' => false]);
$this->addPlugin('CakeDC/Api', ['bootstrap' => true, 'routes' => true]);
```

## OPTIONAL: User Plugin

We rely on CakeDC/Users plugin for Auth, in case you need to define Auth for your API,
ensure *CakeDC/Users* plugin is installed and loaded from your `bootstrap.php`.

### Configuration and Loading

```
Configure::write('Users.config', ['users']);
Plugin::load('CakeDC/Users', ['bootstrap' => false, 'routes' => true]);
```
Check more details about how CakeDC/Users plugin could be configured here: https://github.com/CakeDC/users/blob/master/Docs/Documentation/Configuration.md
