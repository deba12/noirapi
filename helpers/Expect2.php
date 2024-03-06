<?php
/**
 * @noinspection PhpUnused
 * @noinspection PhpUnusedAliasInspection
 */
declare(strict_types=1);

namespace noirapi\helpers;

use DateTimeZone;
use Exception;
use Nette;
use Nette\Schema\Elements\Type;
use Nette\Schema\Schema;
use noirapi\helpers\Schema\Ascii;
use noirapi\helpers\Schema\Cidr;
use noirapi\helpers\Schema\Ip;
use noirapi\helpers\Schema\DateTime;
use noirapi\helpers\Schema\Date;
use noirapi\helpers\Schema\Domain;
use noirapi\helpers\Schema\Ips;
use noirapi\helpers\Schema\Json;
use noirapi\helpers\Schema\Numeric;
use noirapi\helpers\Schema\Recaptcha;
use noirapi\helpers\Schema\Time;
use noirapi\helpers\Schema\Url;
use RuntimeException;

/** @psalm-api  */
final class Expect2 {

    use Nette\SmartObject;

    /**
     * @param string $format
     * @param DateTimeZone|null $timeZone
     * @return DateTime
     * @throws Exception
     */
    public static function dateTime(string $format = 'Y-m-d H:i:s', ?DateTimeZone $timeZone = null): DateTime {
        return new DateTime($format, $timeZone);
    }

    /**
     * @param string $format
     * @param DateTimeZone|null $timeZone
     * @return Date
     * @throws Exception
     */
    public static function date(string $format = 'Y-m-d', ?DateTimeZone $timeZone = null): Date {
        return new Date($format, $timeZone);
    }

    /**
     * @param string $format
     * @param DateTimeZone|null $timeZone
     * @return DateTime
     * @throws Exception
     */
    public static function time(string $format = 'H:i', ?DateTimeZone $timeZone = null): DateTime {
        return new Time($format, $timeZone);
    }

    /**
     * @return Ip
     */
    public static function Ip(): Ip {
        return new Ip();
    }


    public static function Ips(): Ips {
        return new Ips();
    }

    /**
     * @return Domain
     */
    public static function Domain(): Domain {
        return new Domain();
    }

    public static function Numeric(): Numeric {
        return new Numeric();
    }

    /**
     * @return Ascii
     */
    public function Ascii(): Ascii {
        return new Ascii();
    }

    /**
     * @return Url
     */
    public static function Url(): Url {
        return new Url();
    }

    /**
     * @return Json
     */
    public static function Json(): Json {
        return new Json();
    }

    /**
     * @return Recaptcha
     */
    public static function Recaptcha(): Recaptcha {
        return new Recaptcha();
    }

    /**
     * @param bool $multiple
     * @return Cidr
     */
    public static function Cidr(bool $multiple = false): Cidr {
        return new Cidr($multiple);
    }

    /**
     * @param $callable
     * @param mixed ...$params
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
