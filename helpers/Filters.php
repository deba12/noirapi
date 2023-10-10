<?php /** @noinspection UnknownInspectionInspection */
/** @noinspection PhpUnused */
declare(strict_types=1);

namespace noirapi\helpers;
use JsonException;
use noirapi\Exceptions\FilterNotFoundException;

/** @psalm-api  */
class Filters {

    /**
     * @param string $filter
     * @return array
     * @throws FilterNotFoundException
     */
    public static function init(string $filter): array {

        if(class_exists(\app\lib\Filters::class) && method_exists(\app\lib\Filters::class, $filter)) {
            return [\app\lib\Filters::class, $filter];
        }

        if (method_exists(__CLASS__, $filter)) {
            return [__CLASS__, $filter];
        }

        throw new FilterNotFoundException('Filter: ' . $filter . ' does not exists');

    }

    /**
     * @param string $string
     * @return string
     */
    public static function urlencode(string $string): string {
        return urlencode($string);
    }

    /**
     * @param string $string
     * @return string
     */
    public static function urldecode(string $string): string {
        return urldecode($string);
    }

    /**
     * @param string $string
     * @return string
     */
    public static function html_entity_decode(string $string): string {
        return html_entity_decode($string);
    }

    /**
     * @param string $date
     * @param string $format
     * @return string
     */
    public static function date_format(string $date, string $format): string {
        return date($format, strtotime($date));
    }

    /**
     * @param int|bool|string $bool
     * @return int
     */
    public static function inverse(int|bool|string $bool):int {
        /** @noinspection InArrayCanBeUsedInspection */
        if($bool === 1 || $bool === true || $bool === "1") {
            return 0;
        }
        return 1;
    }

    /**
     * @param string $data
     * @return string
     */
    public static function base64_encode(string $data): string {
        return base64_encode($data);
    }

    /**
     * @param object|array $data
     * @return string
     * @throws JsonException
     */
    public static function json_prettify(object|array $data): string {
        return json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
    }

}
