<?php

declare(strict_types=1);

include(__DIR__ . '/include.php');

/**
 * @noinspection PhpUndefinedClassInspection
 * @psalm-suppress UndefinedClass
 */
if (class_exists(App\App::class) && method_exists(App\App::class, 'run')) {
    $app = new App\App();
    $app->run();
} else {
    throw new RuntimeException('app\App class not found or does not have run method');
}
