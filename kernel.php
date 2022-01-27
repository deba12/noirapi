<?php
/**
 * @noinspection PhpUnused
 * @noinspection UnknownInspectionInspection
 * @noinspection PhpUnhandledExceptionInspection
 */
declare(strict_types = 1);

use noirapi\Config;
use noirapi\Exceptions\ConfigException;
use noirapi\lib\Route;
use Tracy\Debugger;

mb_internal_encoding('UTF-8');
error_reporting(E_ALL);
ini_set('display_errors', "0");

define('ROOT', dirname(__DIR__));
const APPROOT = ROOT . '/app';
const WWWROOT = ROOT . '/htdocs';

if(file_exists(ROOT . '/vendor' . DIRECTORY_SEPARATOR . 'autoload.php')) {
    require_once(ROOT . '/vendor' . DIRECTORY_SEPARATOR . 'autoload.php');
}

const PATH_VIEWS = APPROOT . '/views/';
const PATH_TEMPLATES = APPROOT . '/templates/';
const PATH_LAYOUTS = APPROOT . '/layouts/';
const PATH_TEMP = ROOT . '/temp/';
const PATH_LOGS = ROOT . '/logs/';

$config = getenv('CONFIG');

if(empty($config)) {
    $config = $_SERVER['SERVER_NAME'];
}

if(empty($config)) {
    throw new RuntimeException('CONFIG environment must be set');
}

Config::init($config);

Debugger::$strictMode = E_ALL;

try {
    $dev = Config::get('dev');
} /** @noinspection PhpRedundantCatchClauseInspection */ catch (ConfigException $e) {
    $dev = false;
}

if($dev === true) {
    //we are missing dome debug events in Tracy that's why we start session so early
    if(session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
	Debugger::enable(Debugger::DEVELOPMENT, PATH_LOGS);
} else {
	Debugger::enable(Debugger::PRODUCTION, PATH_LOGS);
}

if($_SERVER['PHP_SELF'] === '/index.php') {
    Config::set('https', isset($_SERVER['HTTPS']));
    Config::set('domain', $_SERVER[ 'SERVER_NAME' ]);
    /** @noinspection PhpUnhandledExceptionInspection */
    new Route($_SERVER, $_GET, $_POST, $_FILES, $_COOKIE);
}
