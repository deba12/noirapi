<?php /** @noinspection UnknownInspectionInspection */
/** @noinspection PhpUnused */
declare(strict_types=1);

namespace noirapi\helpers;
use JsonException;
use noirapi\Exceptions\FilterNotFoundException;

class Filters {

    /**
     * @param $filter
     * @return array
     * @throws FilterNotFoundException
     */
    public static function init($filter): array {

        if(class_exists(\app\lib\Filters::class) && method_exists(\app\lib\Filters::class, $filter)) {
            return [\app\lib\Filters::class, $filter];
        }

        if (method_exists(__CLASS__, $filter)) {
            return [__CLASS__, $filter];
        }

        throw new FilterNotFoundException('Filter: ' . $filter . ' does not exists');

    }

    /**
     * @param $string
     * @return string
     * @noinspection SpellCheckingInspection
     */
    public static function urlencode($string): string {
        return urlencode($string);
    }

    /**
     * @param $string
     * @return string
     * @noinspection SpellCheckingInspection
     */
    public static function urldecode($string): string {
        return urldecode($string);
    }

    /**
     * @param $string
     * @return string
     */
    public static function html_entity_decode($string): string {
        return html_entity_decode($string);
    }

    /**
     * @param $date
     * @param $format
     * @return string
     */
    public static function date_format($date, $format): string {
        return date($format, strtotime($date));
    }

    /**
     * @param $bool
     * @return int
     * @noinspection TypeUnsafeComparisonInspection
     */
    public static function inverse($bool):int {
        if($bool == 1 || $bool === true) {
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
