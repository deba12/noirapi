<?php
declare(strict_types=1);

namespace noirapi\helpers\View;

class BreadCrumb {

    private static array $items = [];

    public static function addItem(int|string $name, ?string $url = null, bool $active = false) {

        self::$items[] = [
            'name'      => (string)$name,
            'url'       => $url,
            'active'    => $active
        ];

    }

    public static function getItems() {

        return self::$items;

    }

}
