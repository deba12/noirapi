<?php
/** @noinspection ContractViolationInspection */
declare(strict_types=1);

namespace noirapi\helpers\Schema;

use DateInterval;
use DateTimeImmutable;
use Exception;
use Nette\Schema\Context;
use Nette\Schema\Message;
use function count;
use function gettype;
use function is_string;

class Time extends DateTime {

    private ?DateTimeImmutable $date = null;

    /**
     * @param string $date
     * @return $this
     */
    public function date(string $date): self {
        $this->date = DateTimeImmutable::createFromFormat('Y-m-d', $date);
        return $this;
    }

    /**
     * @param $value
     * @param Context $context
     * @return DateTimeImmutable|null|string
     * @throws Exception
     */
    public function normalize($value, Context $context): DateTimeImmutable|string|null {

        if($this->nullable && empty($value)) {
            return null;
        }

        // Must be string or empty (null / 0 / false /
        if (!is_string($value) && !empty($value)) {
            $type = gettype($value);
            /** @noinspection UnusedFunctionResultInspection */
            $context->addError("The option %path% expects Hours:minutes, $type given.", Message::PATTERN_MISMATCH);
            return null;
        }

        if (!$this->nullable && empty($value)) {
            /** @noinspection UnusedFunctionResultInspection */
            $context->addError('The option %path% expects not-nullable Hours:minutes, nothing given.', Message::PATTERN_MISMATCH);
            return null;
        }

        $normalized = DateTimeImmutable::createFromFormat($this->format, !empty($this->date) ? $this->date->format($this->format) : date($this->format), $this->timeZone);
        if (!$normalized instanceof DateTimeImmutable) {
            /** @noinspection UnusedFunctionResultInspection */
            $context->addError("The option %path% expects Time to match pattern '$this->format', '$value' given.", Message::PATTERN_MISMATCH);
            return null;
        }

        $arr = explode(':', $value);

        if(count($arr) !== 2) {
            /** @noinspection UnusedFunctionResultInspection */
            $context->addError("The option %path% expects Time to match pattern '$this->format', '$value' given.", Message::PATTERN_MISMATCH);
            return null;
        }

        if(!empty($this->output_format)) {
            return $normalized->format($this->output_format);
        }

        return $normalized->add(new DateInterval('PT' . $arr[0] . 'H' . $arr[1] . 'M'));

    }

}
