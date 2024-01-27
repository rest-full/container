# Rest-full Container

## About Rest-full Container

Rest-full Container is a small part of the Rest-Full framework.

You can find the application at: [rest-full/app](https://github.com/rest-full/app) and you can also see the framework skeleton at: [rest-full/rest-full](https://github.com/rest-full/rest-full).

## Installation

* Download [Composer](https://getcomposer.org/doc/00-intro.md) or update `composer self-update`.
* Run `php composer.phar require rest-full/container` or composer installed globally `compser require rest-full/container` or composer.json `"rest-full/container": "1.0.0"` and install or update.

## Usage

This Container
```
<?php

require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/../config/pathServer.php';

use Restfull\Container\Instances;

$instance = new Instances();
echo $instance->renameClass('c:\xampp\htdocs\Restfull/Container\Casa');
```
## License

The rest-full framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).