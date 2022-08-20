<?php
/** @noinspection ContractViolationInspection */
declare(strict_types=1);

namespace noirapi\helpers\Schema;

use DateTimeImmutable;
use DateTimeZone;
use Nette\Schema\Context;

class Date extends DateTime {

    private ?DateTimeImmutable $time = null;

    public function __construct($format = 'Y-m-d', ?DateTimeZone $timeZone = null) {
        parent::__construct($format, $timeZone);
    }

    public function time(string $time): self {
        $this->time = DateTimeImmutable::createFromFormat('H:i', $time);
        return $this;
    }

    /**
     * @param $value
     * @param Context $context
     * @return string|DateTimeImmutable|null
     */
    public function normalize($value, Context $context): string|null|DateTimeImmutable {

        $normalized = parent::normalize($value, $context);

        if ($normalized instanceof DateTimeImmutable) {

            if($this->time === null) {
                $normalized = $normalized->setTime(0, 0);
            } else {
                $normalized = $normalized->setTime((int)$this->time->format('G'), (int)$this->time->format('i'));
            }

            if(!empty($this->output_format)) {
                return $normalized->format($this->output_format);
            }

        }

        return $normalized;

    }

}
