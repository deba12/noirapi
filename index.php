<?php declare(strict_types = 1);

mb_internal_encoding('UTF-8');
error_reporting(E_ALL);

define('ROOT', dirname(__FILE__, 1) . DIRECTORY_SEPARATOR);
define('APPROOT', ROOT . 'app' . DIRECTORY_SEPARATOR);
define('WWWROOT', ROOT . 'htdocs' . DIRECTORY_SEPARATOR);
require('kernel.php');