<?php

declare(strict_types=1);

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$applicationPath = dirname(__DIR__, 2)
    . '/customer-management_app';

if (file_exists(
    $maintenance = $applicationPath
        . '/storage/framework/maintenance.php'
)) {
    require $maintenance;
}

require $applicationPath . '/vendor/autoload.php';

/** @var \Illuminate\Foundation\Application $app */
$app = require_once $applicationPath . '/bootstrap/app.php';

$app->handleRequest(Request::capture());
