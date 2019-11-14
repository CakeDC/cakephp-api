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
You can configure the API overwriting the api.php, how?
we need to create an **api.php** file in the *config* folder. You can copy the existing configuration file 
under `vendor/cakedc/cakephp-api/config/api.php` and customize it for your application.
Remember to load the new configuration file in `bootstrap.php`

```php
Plugin::load('CakeDC/Api', ['bootstrap' => false, 'routes' => true]);
Configure::load('api');
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
