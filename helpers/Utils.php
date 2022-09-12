<?php
/** @noinspection PhpUnused */
declare(strict_types=1);

namespace noirapi\helpers;

use Exception;
use Nette\StaticClass;

class Utils {

    use StaticClass;

    /**
     * @param int $len
     * @return string
     * @throws Exception
     */
    public static function random(int $len = 16): string {
        $chars = '23456789abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ';
        $res = '';

        for($i = 1; $i <= $len; $i++) {
            $res .= $chars[random_int(1, strlen($chars)-1)];
        }

        return $res;
    }

    /**
     * @param string $cmd
     * @return void
     */
    public static function backgroundTask(string $cmd): void {
        proc_close(proc_open( "$cmd &", [], $pipes ));
    }

    public static function returnNull($object) {

        if(empty($object)) {
            return null;
        }

        return $object;

    }

    /**
     * @return string
     * @throws Exception
     */
    public static function guidV4(): string {

        $data = random_bytes(16);

        // Set version to 0100
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        // Set bits 6-7 to 10
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        // Output the 36 character UUID.
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));

    }

}
