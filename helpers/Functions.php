<?php

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

}
