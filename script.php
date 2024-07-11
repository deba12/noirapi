<?php
declare(strict_types=1);

/** @psalm-suppress MissingFile */
include(__DIR__ . '/include.php');

/**
 * @noinspection PhpUndefinedClassInspection
 * @psalm-suppress UndefinedClass
 */
if(class_exists(app\App::class) && method_exists(app\App::class, 'run')) {
    $app = new app\App();
    $app->run();
} else {
    throw new RuntimeException('app\App class not found or does not have run method');
}
