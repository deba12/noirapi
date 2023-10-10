<?php
/**
 * @noinspection PhpUnused
 * @noinspection UnusedFunctionResultInspection
 * @noinspection ContractViolationInspection
 * @noinspection UnknownInspectionInspection
 */
declare(strict_types=1);

namespace noirapi\helpers\Schema;

use DateTimeZone;
use Exception;
use Nette\Schema\Context;
use Nette\Schema\Message;
use Nette\Schema\Schema;
use function gettype;
use function is_string;

/** @psalm-api  */
class DateTime implements Schema {

    protected bool $required = false;
    protected string $name;
    protected bool $nullable = false;
    protected string $format;
    protected ?DateTimeZone $timeZone;
    protected string $output_format;

    protected \DateTime|null $date = null;
    protected \DateTime|null $time = null;

    /**
     * @param string $format
     * @param DateTimeZone|null $timeZone
     * @throws Exception
     */
    public function __construct(string $format = 'Y-m-d H:i:s', ?DateTimeZone $timeZone = null) {
        $this->format = $format;
        $this->timeZone = $timeZone ?? new DateTimeZone(date_default_timezone_get());
        $this->name = $this->name ?? 'DateTime';
    }

    public function setTime(string $time, string $format): self {
        $this->time = \DateTime::createFromFormat($format, $time, $this->timeZone);

        return $this;
    }

    public function setDate(string $date, string $format): self {
        $this->date = \DateTime::createFromFormat($format, $date, $this->timeZone);

        return $this;
    }

    public function required(bool $state = true): self {
        $this->required = $state;
        return $this;
    }

    public function nullable(bool $state = true): self {
        $this->nullable = $state;
        return $this;
    }

    public function format(string $format): self {
        $this->output_format = $format;
        return $this;
    }

    /**
     * @param $value
     * @param Context $context
     * @return string|\DateTime|null
     */
    public function normalize($value, Context $context): string|null|\DateTime {

        if($this->nullable && empty($value)) {
            return null;
        }

        if (!is_string($value) && empty($value)) {
            $type = gettype($value);
            $context->addError("The option %path% expects $this->name($this->format), $type($value) given.", Message::PATTERN_MISMATCH);
            return null;
        }

        if (!$this->nullable && empty($value)) {
            $context->addError("The option %path% expects not-nullable $this->name, nothing given.", Message::PATTERN_MISMATCH);
            return null;
        }

        $normalized = null;

        if(!empty($value)) {
            $normalized = \DateTime::createFromFormat($this->format, $value, $this->timeZone);
        }

        if(!empty($this->date)) {

            if(!empty($normalized)) {
                $normalized->setDate((int)$this->date->format('Y'), (int)$this->date->format('m'), (int)$this->date->format('d'));
            } else {
                $normalized = $this->date;
            }

        }

        if(isset($this->time)) {
            if(!empty($normalized)) {
                $normalized->setTime((int)$this->time->format('H'), (int)$this->time->format('i'), (int)$this->time->format('s'));
            } else {
                $normalized = $this->date;
            }
        }

        if(empty($normalized)) {
            $context->addError("The option %path% expects $this->name to match pattern '$this->format', '$value' given.", Message::PATTERN_MISMATCH);
            return null;
        }

        if(!empty($this->output_format)) {
            return $normalized->format($this->output_format);
        }

        return $normalized;
    }

    public function merge($value, $base) {
        return $value;
    }

    public function complete($value, Context $context) {
        return $value;
    }

    /**
     * @noinspection ReturnTypeCanBeDeclaredInspection
     * @noinspection PhpMissingReturnTypeInspection
     */
    public function completeDefault(Context $context) {
        if ($this->required) {
            $context->addError('The mandatory option %path% is missing.', Message::MISSING_ITEM);
        }
        return null;
    }

}
