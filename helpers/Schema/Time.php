<?php
/** @noinspection ContractViolationInspection */
declare(strict_types=1);

namespace noirapi\helpers\Schema;

use DateTimeZone;
use Exception;

/**
 * @psalm-api
 * @psalm-suppress PropertyNotSetInConstructor
 */
class Time extends DateTime
{

    /**
     * @param string $format
     * @param DateTimeZone|null $timeZone
     * @throws Exception
     *
     */
    public function __construct(string $format = 'H:i:s', ?DateTimeZone $timeZone = null)
    {
        parent::__construct($format, $timeZone, 'time');
    }

}
