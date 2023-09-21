<?php
/** @noinspection ContractViolationInspection */
declare(strict_types=1);

namespace noirapi\helpers\Schema;

use DateTimeZone;

class Time extends DateTime {

    public function __construct(string $format = 'H:i:s', ?DateTimeZone $timeZone = null) {
        $this->name = 'Time';
        parent::__construct($format, $timeZone);
    }

}
