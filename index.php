<?php declare(strict_types = 1);

mb_internal_encoding('UTF-8');
error_reporting(E_ALL);

define('ROOT', dirname(__DIR__) . DIRECTORY_SEPARATOR);
const APPROOT = ROOT . 'app' . DIRECTORY_SEPARATOR;
const WWWROOT = ROOT . 'htdocs' . DIRECTORY_SEPARATOR;
require('kernel.php');
