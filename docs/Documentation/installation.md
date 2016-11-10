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

## Customization
You can custom the api overwriting the api.php, how?
we need to create an **api.php** file in the *config* folder. It need to be write in the same standard of the original. You can take a look in CakeDC/Api/config/api.php to know how to write it.
In this case API plugin initialization would be a bit diferent.

```
Plugin::load('CakeDC/Api', ['bootstrap' => false, 'routes' => true]);
Configure::load('api');

``` 

## User Plugin
We need to have *CakeDC/Users* plugin installed, because we use it for auth. Below you can see what you need to do with users plugin.

## Composer

```
composer require cakedc/users
```

## Configuration and Loading

```
Configure::write('Users.config', ['users']);
Plugin::load('CakeDC/Users', ['bootstrap' => false, 'routes' => true]);
``` 
