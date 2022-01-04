<?php
/** @noinspection PhpUnused */
declare(strict_types=1);

namespace noirapi\helpers;

use Exception;

class Functions {

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

}
