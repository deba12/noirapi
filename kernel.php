<?php
/**
 * @noinspection PhpUnused
 * @noinspection UnknownInspectionInspection
 * @noinspection PhpUnhandledExceptionInspection
 */
declare(strict_types = 1);

use noirapi\Config;
use noirapi\lib\Route;

include(__DIR__ . '/include.php');

// If the request is for the index.php use the router
if($_SERVER['PHP_SELF'] === '/index.php') {
    Config::set('https', isset($_SERVER['HTTPS']));
    Config::set('domain', $_SERVER[ 'SERVER_NAME' ]);
    /** @noinspection PhpUnhandledExceptionInspection */
    echo Route::fromGlobals($_SERVER, $_GET, $_POST, $_FILES, $_COOKIE)->serve();
}
