<?php
/** @noinspection PhpUnused */
declare(strict_types=1);

namespace noirapi\helpers;

class Session {

    /**
     * @param string $namespace
     * @param string $key
     * @return mixed
     */
    public static function get(string $namespace, string $key): mixed {
        return $_SESSION[$namespace][$key] ?? null;
    }

    /**
     * @param string $namespace
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public static function set(string $namespace, string $key, mixed $value): void {
        $_SESSION[$namespace][$key] = $value;
    }

    /**
     * @param string $namespace
     * @param string $key
     * @return bool
     */
    public function has(string $namespace, string $key): bool {
        return isset($_SESSION[$namespace][$key]);
    }

    /**
     * @param string $namespace
     * @param string $key
     * @return void
     */
    public function remove(string $namespace, string $key): void {
        unset($_SESSION[$namespace][$key]);
    }

}
