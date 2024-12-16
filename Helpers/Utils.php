<?php

/** @noinspection PhpUnused */

declare(strict_types=1);

namespace Noirapi\Helpers;

use Exception;
use Nette\StaticClass;
use Random\Randomizer;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use stdClass;

use function array_key_exists;
use function array_slice;
use function bin2hex;
use function chr;
use function count;
use function defined;
use function get_class;
use function is_array;
use function is_object;
use function ord;
use function proc_close;
use function proc_open;
use function str_split;
use function strlen;
use function vsprintf;

/** @psalm-api  */
class Utils
{
    use StaticClass;

    /**
     * @param int $len
     * @return string
     * @throws Exception
     */
    public static function random(int $len = 16): string
    {
        /** @noinspection SpellCheckingInspection */
        $chars = '23456789abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ';
        $res = '';

        for ($i = 1; $i <= $len; $i++) {
            $res .= $chars[random_int(1, strlen($chars) - 1)];
        }

        return $res;
    }

    /**
     * @param bool $long
     * @return string
     * @throws Exception
     */
    public static function generateKey(bool $long = true): string
    {

        $algo = $long ? 'sha256' : 'sha1';

        return hash($algo, random_bytes(64));
    }

    /**
     * @param int $min
     * @param int $max
     * @return float
     * @throws Exception
     */
    public static function randomFloat(int $min = 0, int $max = PHP_INT_MAX): float
    {
        return random_int($min, $max - 1) / $max;
    }

    /**
     * @param int $min
     * @param int $max
     * @return int
     * @throws Exception
     */
    public static function randomInt(int $min = 0, int $max = PHP_INT_MAX): int
    {
        return random_int($min, $max);
    }

    /**
     * @param int $len
     * @return string
     * @throws Exception
     */
    public static function randomString(int $len = 8): string
    {
        /** @noinspection SpellCheckingInspection */
        $chars = '23456789abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ';
        $res = '';

        for ($i = 1; $i <= $len; $i++) {
            $res .= $chars[random_int(1, strlen($chars) - 1)];
        }

        return $res;
    }

    /**
     * @param string $cmd
     * @return void
     */
    public static function backgroundTask(string $cmd): void
    {
        proc_close(proc_open("$cmd &", [], $pipes));
    }

    /**
     * @param mixed $object
     * @return mixed|null
     */
    public static function returnNull(mixed $object): mixed
    {

        if (empty($object)) {
            return null;
        }

        return $object;
    }

    /**
     * @return string
     * @throws Exception
     */
    public static function guidV4(): string
    {

        $data = random_bytes(16);

        // Set version to 0100
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        // Set bits 6-7 to 10
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        // Output the 36-character UUID.
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * @param string|object $class
     * @param int|string $depth
     * @return string
     */
    public static function getClassName(string|object $class, int|string $depth = 1): string
    {

        $depth = (int) $depth;

        if (is_object($class)) {
            $class = get_class($class);
        }

        $path = explode('\\', $class);

        if ($depth > count($path)) {
            $depth = count($path);
        }

        return implode('\\', array_slice($path, - $depth));
    }

    /**
     * @param array $array
     * @return array
     * @throws Exception
     */
    public static function array_shuffle(array $array): array // phpcs:ignore
    {

        return (new Randomizer())->shuffleArray($array);
    }

    /**
     * @return bool
     */
    public static function is_tty(): bool // phpcs:ignore
    {
        return defined('STDOUT') && posix_isatty(STDOUT);
    }

    /**
     * @param string $string
     * @return string
     * @noinspection SpellCheckingInspection
     */
    public static function mb_ucfirst(string $string): string // phpcs:ignore
    {
        return mb_strtoupper(mb_substr($string, 0, 1)) . mb_substr($string, 1);
    }

    /**
     * @param array|object $input
     * @param string|null $className
     * @param bool $remove_missing
     * @return object
     */
    public static function toObject(array|object $input, ?string $className = null, bool $remove_missing = true): object
    {
        if ($className === null) {
            $class = new stdClass();

            foreach ($input as $key => $value) {
                $class->$key = $value;
            }

            return $class;
        }

        /** @psalm-suppress InvalidStringClass */
        $class = new $className();

        try {
            $properties = (new ReflectionClass($class))->getProperties(ReflectionProperty::IS_PUBLIC);
        } catch (ReflectionException) {
            return (object) $input;
        }

        foreach ($properties as $property) {
            $name = $property->getName();

            if (is_array($input)) {
                if (isset($input[$name])) {
                    $class->$name = $input[$name];
                } elseif ($remove_missing) {
                    unset($class->$name);
                }
            } elseif (isset($input->$name)) {
                $class->$name = $input->$name;
            } elseif ($remove_missing) {
                unset($class->$name);
            }
        }

        return $class;
    }

    /**
     * @param mixed $class
     * @param bool $public_only
     * @return array
     * @throws ReflectionException
     */
    public static function getClassProperties(mixed $class, bool $public_only = true): array
    {
        $result = [];

        $properties = (new ReflectionClass($class))->getProperties($public_only ? ReflectionProperty::IS_PUBLIC : null);

        foreach ($properties as $property) {
            $result[] = $property->getName();
        }

        return $result;
    }

    /**
     * @param mixed $var
     * @param mixed $scope
     * @return int|string|void
     */
    public static function var_name(mixed &$var, mixed $scope = false) // phpcs:ignore
    {
        $old = $var;
        if (
            ($key = array_search(
                $var = 'unique' . mt_rand() . 'value',
                ! $scope ? $GLOBALS : $scope,
                true
            )) && $var = $old
        ) {
            return $key;
        }
    }

    /**
     * @param array $a1
     * @param array $a2
     * @return array
     * @noinspection TypeUnsafeComparisonInspection
     */
    public static function array_diff_recursive(array $a1, array $a2): array // phpcs:ignore
    {
        $r = [];

        foreach ($a1 as $k => $v) {
            if (array_key_exists($k, $a2)) {
                if (is_array($v)) {
                    $rad = self::array_diff_recursive($v, $a2[$k]);
                    if (count($rad)) {
                        $r[$k] = $rad;
                    }
                } elseif ($v != $a2[$k]) {
                    $r[$k] = $v;
                }
            } else {
                $r[$k] = $v;
            }
        }

        return $r;
    }

    /**
     * @param string $data
     * @return string
     */
    public static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * @param string $data
     * @return string
     */
    public static function base64UrlDecode(string $data): string
    {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '='));
    }
}
