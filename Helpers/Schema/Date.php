<?php

/** @noinspection ContractViolationInspection */

declare(strict_types=1);

namespace Noirapi\Helpers\Schema;

use DateTimeZone;

/** @psalm-api  */
class Date extends DateTime
{
    public function __construct(string $format = 'Y-m-d', ?DateTimeZone $timeZone = null)
    {
        parent::__construct($format, $timeZone, 'date');
    }
}
