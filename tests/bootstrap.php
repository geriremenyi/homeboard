<?php
// Set timezone
date_default_timezone_set('Europe/Budapest');

// Prevent session cookies
ini_set('session.use_cookies', 0);

// Include settings
require_once('../resty-settings.php');

// Enable Composer autoloader
$autoloader = require dirname(__DIR__) . '/vendor/autoload.php';

// Register test classes
$autoloader->addPsr4('Resty\\', __DIR__ . '/..', true);
$autoloader->addPsr4('Application\\', __DIR__ . '/..', true);