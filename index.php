<?php
require_once('resty-settings.php');

// Load dependencies
$autoloader = require dirname(__DIR__) . '/vendor/autoload.php';
$autoloader->addPsr4('Resty\\', __DIR__ . '/..', true);
$autoloader->addPsr4('Application\\', __DIR__ . '/..', true);

new \Resty\Utility\Application();