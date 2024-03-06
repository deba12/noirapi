<?php
declare(strict_types=1);

include(__DIR__ . '/include.php');

/** @noinspection PhpUndefinedClassInspection */
if(class_exists(app\App::class) && method_exists(app\App::class, 'run')) {
    $app = new app\App();
    $app->run();
} else {
    throw new RuntimeException('app\App class not found or does not have run method');
}
