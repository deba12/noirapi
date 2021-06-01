<?php /** @noinspection PhpUnused */
/** @noinspection PhpUnusedAliasInspection */

declare(strict_types=1);

namespace noirapi\helpers;

use Nette;
use Nette\Schema\Elements\Type;
use Nette\Schema\Schema;
use noirapi\helpers\Schema\Ip;
use noirapi\helpers\Schema\DateTime;
use noirapi\helpers\Schema\Date;
use noirapi\helpers\Schema\Domain;
use noirapi\helpers\Schema\Numeric;
use RuntimeException;

final class Expect2 {

    use Nette\SmartObject;

    public static function date($format = 'Y-m-d', ?\DateTimeZone $timeZone = null): Date {
        return new Date($format, $timeZone);
    }

    public static function dateTime($format = 'Y-m-d H:i:s', ?\DateTimeZone $timeZone = null): DateTime {
        return new DateTime($format, $timeZone);
    }

    public static function Ip(): Ip {
        return new Ip();
    }

    public static function Domain(): Domain {
        return new Domain();
    }

    public static function numeric(): Numeric {
        return new Numeric();
    }

    /**
     * @param $callable
     * @param ...$params
     * @return Schema|null
     */
    public static function custom($callable, ...$params): ?Schema {

        if(is_callable($callable)) {
            return $callable($params);
        }

        if (is_string($callable) && class_exists($callable)) {
            $instance = new $callable($params);
            if(!$instance instanceof Schema) {
                throw new RuntimeException($callable . ' does not implements Schema interface');
            }
        }

        if($callable instanceof Schema) {
            return $callable;
        }

        throw new RuntimeException('Called class must implement schema interface');

    }

}
