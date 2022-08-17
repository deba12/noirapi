<?php
declare(strict_types=1);

namespace noirapi\helpers\View;

class BreadCrumb {

    private static array $items = [];

    /**
     * @param int|string $name
     * @param string|null $url
     * @param bool|null $active
     * @return void
     */
    public static function addItem(int|string $name, ?string $url = null, ?bool $active = null): void {

        self::$items[] = [
            'name'      => (string)$name,
            'url'       => $url,
            'active'    => $active
        ];

    }

    /**
     * @return array
     */
    public static function getItems(): array {

        return self::$items;

    }

}
