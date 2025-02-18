<?php //phpcs:ignore

/**
 * @noinspection PhpUnused
 * @noinspection SpellCheckingInspection
 */

declare(strict_types=1);

use Noirapi\Config;
use Noirapi\Helpers\TracyFileSession;
use Noirapi\Helpers\Utils;
use Tracy\Debugger;
use Tracy\NativeSession;

mb_internal_encoding('UTF-8');
error_reporting(E_ALL);
ini_set('display_errors', '0');

define('ROOT', dirname(__DIR__));
const APPROOT = ROOT . '/app';
const WWWROOT = ROOT . '/htdocs';

if (file_exists(ROOT . '/vendor' . DIRECTORY_SEPARATOR . 'autoload.php')) {
    /** @psalm-suppress MissingFile */
    require_once(ROOT . '/vendor' . DIRECTORY_SEPARATOR . 'autoload.php');
}

const PATH_VIEWS = APPROOT . '/views/';
const PATH_TEMPLATES = APPROOT . '/templates/';
const PATH_LAYOUTS = APPROOT . '/layouts/';
const PATH_TEMP = ROOT . '/temp/';
const PATH_LOGS = ROOT . '/logs/';

$config = getenv('CONFIG');

if (! is_string($config)) {
    $config = $_SERVER['SERVER_NAME'] ?? $_SERVER['HTTP_HOST'] ?? 'default';
}

if (empty($config)) {
    if (! Config::defaultConfigAvailable()) {
        throw new RuntimeException('CONFIG environment must be set');
    }
    $config = 'default';
}

/** @noinspection PhpUnhandledExceptionInspection */
Config::init($config);

define('SESSION_ROOT', Config::get('SESSION_ROOT') ?? (ROOT . '/sessions'));

Config::set('is_https', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on');

Debugger::$strictMode = E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED; // all errors except deprecated notices
Debugger::$showLocation = true;
Debugger::$logSeverity = E_NOTICE | E_WARNING;

// We have to use our own session storage because Tracy's default session storage is notcleaning up old sessions
/** @noinspection PhpUnhandledExceptionInspection */
Debugger::setSessionStorage((@is_dir($dir = session_save_path())
    || @is_dir($dir = ini_get('upload_tmp_dir'))
    || @is_dir($dir = sys_get_temp_dir())
    || ($dir = PATH_LOGS))
    ? new TracyFileSession($dir)
    : new NativeSession());

$dev = Config::get('dev');
$dev_ips = Config::get('dev_ips');

if (Utils::isDev($_SERVER["REMOTE_ADDR"] ?? "")) {
    //we are missing some debug events in Tracy that's why we start session so early
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    Debugger::enable(Debugger::Development, PATH_LOGS);
} else {
    Debugger::enable(Debugger::Production, PATH_LOGS, Config::get('dev_email'));
}
