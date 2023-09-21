<?php
/** @noinspection ContractViolationInspection */
declare(strict_types=1);

namespace noirapi\helpers\Schema;

use DateTimeZone;

class Date extends DateTime {

    public function __construct($format = 'Y-m-d', ?DateTimeZone $timeZone = null) {
        $this->name = 'Date';
        parent::__construct($format, $timeZone);
    }

}
