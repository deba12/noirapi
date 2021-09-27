<?php
/**
 * @noinspection PhpUnused
 * @noinspection UnknownInspectionInspection
 * @noinspection PhpUnhandledExceptionInspection
 */
declare(strict_types = 1);

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

//directory paths
const PATH_VIEWS = APPROOT . '/views' . DIRECTORY_SEPARATOR;
const PATH_TEMPLATES = APPROOT . '/templates' . DIRECTORY_SEPARATOR;
const PATH_LAYOUTS = APPROOT . '/layouts' . DIRECTORY_SEPARATOR;

//default extension used ? by default .php
const DEFAULT_EXTENSION = '.php';

if(isset($_SERVER['SERVER_NAME'])) {
    $conf = APPROOT . '/config/' . $_SERVER['SERVER_NAME'] . DEFAULT_EXTENSION;
} else {
    $env = getenv('CONFIG');
    if(is_string($env)) {
        $conf = APPROOT . '/config/' . $env . DEFAULT_EXTENSION;
    }
}

if(empty($conf) || !is_readable($conf)) {
    throw new RuntimeException("Unable to locate config");
}

define('CONFIG', $_SERVER['SERVER_NAME'] ?? getenv('CONFIG'));
require_once($conf);

Debugger::$strictMode = E_ALL;

if(defined('DEV') && DEV === 1) {
    //we are missing dome debug events in Tracy that's why we start session so early
    session_start();
	Debugger::enable(Debugger::DEVELOPMENT, ROOT . '/logs/');
} else {
	Debugger::enable(Debugger::PRODUCTION, ROOT . '/logs/');
}

$loader = new Nette\Loaders\RobotLoader;
$loader->addDirectory(__DIR__ . DIRECTORY_SEPARATOR . '.');

//application controllers
if(file_exists(APPROOT)) {
	$loader->addDirectory(APPROOT);
}

$loader->setTempDirectory(ROOT . DIRECTORY_SEPARATOR . 'temp');

if(PHP_SAPI === 'cli') {
	$loader->setAutoRefresh(false);
}

$loader->register();

if($_SERVER['PHP_SELF'] === '/index.php') {
    define('BASE_SCHEME', isset($_SERVER['HTTPS']) ? 'https://': 'http://');
    define('BASE_DOMAIN', $_SERVER['SERVER_NAME']);
    /** @noinspection PhpUnhandledExceptionInspection */
    new Route($_SERVER, $_GET, $_POST, $_FILES, $_COOKIE);
}
