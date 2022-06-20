<?php /** @noinspection UnknownInspectionInspection */
/** @noinspection PhpUnused */
declare(strict_types=1);

namespace noirapi\helpers;
use noirapi\Exceptions\FilterNotFoundException;

class Filters {

    /**
     * @param $filter
     * @param $value
     * @return mixed
     * @throws FilterNotFoundException
     * @noinspection PhpUndefinedNamespaceInspection
     * @noinspection PhpUndefinedClassInspection
     */
    public static function init($filter, $value) {

        if(class_exists(\app\lib\Filters::class) && method_exists(\app\lib\Filters::class, $filter)) {
            $args = func_get_args();
            array_shift($args);
            return call_user_func_array([\app\lib\Filters::class, $filter], $args);
        }

        if (method_exists(__CLASS__, $filter)) {
            $args = func_get_args();
            array_shift($args);
            return call_user_func_array([__CLASS__, $filter], $args);
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
     * @return false|string
     */
    public static function date_format($date, $format) {
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

}
