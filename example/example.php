<?php

require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/../config/pathServer.php';

use Restfull\Container\Instances;

$instance = new Instances();
echo $instance->renameClass('c:\xampp\htdocs\Restfull/Container\Casa');