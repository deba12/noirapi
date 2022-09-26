<?php
declare(strict_types=1);

namespace noirapi;

use Nette\Neon\Neon;
use noirapi\Exceptions\ConfigException;
use function is_array;

class Config {

    private static array $options;
    public static string $config;

    /**
     * @param string $config
     * @return void
     * @throws ConfigException
     */
    public static function init(string $config): void {

        $file = ROOT . '/app/config/' . $config . '.neon';

        //TODO cache config in json with expiration
        if(is_readable($file)) {

            $parsed = Neon::decode(file_get_contents($file));

            if(empty($parsed)) {
                throw new ConfigException('Unable to parse config:' . $file);
            }

            if(is_array($parsed)) {

                foreach($parsed as $key => $value) {
                    self::set($key, $value);
                }

            } else {

                self::set('default', $parsed);

            }

        } else {

            throw new ConfigException('Config file not found:' . $file);

        }

        self::$config = $config;

    }

    /**
     * @param string $option
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $option, mixed $default = null): mixed {

        if(str_contains($option, '.')) {

            $parts = explode('.', $option);

            // here we cache our found key
            $path = [];

            foreach($parts as $part) {
                // empty element
                if(empty($part)) {
                    return $default ?? null;
                }

                if(empty($path)) {
                    if(isset(self::$options[$part])) {
                        $path = self::$options[$part];
                    }
                } else if(isset($path[$part])) {
                    $path = $path[$part];
                } else {
                    return $default ?? null;
                }

            }

            return empty($path) ? $default ?? null : $path;

        }

        return self::$options[$option] ?? $default ?? null;

    }

    /**
     * @param string $option
     * @param mixed $data
     * @return void
     */
    public static function set(string $option, mixed $data): void {

        self::$options[$option] = $data;

    }

    /**
     * @return array
     * @noinspection PhpUnused
     */
    public static function getAll(): array {
        return self::$options;
    }

}
