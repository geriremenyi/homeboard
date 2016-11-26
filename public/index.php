<?php
require_once(dirname(__FILE__) . '/../resty-settings.php');

// Load dependencies
$autoloader = require_once(dirname(__FILE__) . '/../vendor/autoload.php');

// Register test classes
$autoloader->addPsr4('Resty\\', __DIR__ . '/..', true);
$autoloader->addPsr4('Application\\', __DIR__ . '/..', true);

$app = new \Resty\Utility\Application();
$app->start();