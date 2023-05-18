<?php
/**
 * @noinspection PhpUnused
 * @noinspection UnusedFunctionResultInspection
 * @noinspection ContractViolationInspection
 * @noinspection UnknownInspectionInspection
 */
declare(strict_types=1);

namespace noirapi\helpers\Schema;

use DateTimeImmutable;
use DateTimeZone;
use Exception;
use Nette\Schema\Context;
use Nette\Schema\Message;
use Nette\Schema\Schema;
use function gettype;
use function is_string;

class DateTime implements Schema {

    private bool $required = false;
    protected bool $nullable = true;
    protected mixed $format;
    protected ?DateTimeZone $timeZone;
    protected string $output_format;

    /**
     * @param string $format
     * @param DateTimeZone|null $timeZone
     * @throws Exception
     */
    public function __construct(string $format = 'Y-m-d H:i:s', ?DateTimeZone $timeZone = null) {
        $this->format = $format;
        $this->timeZone = $timeZone ?? new DateTimeZone(date_default_timezone_get());
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
     * @return string|DateTimeImmutable|null
     */
    public function normalize($value, Context $context): string|null|DateTimeImmutable {

        if($this->nullable && empty($value)) {
            return null;
        }

        // Must be string or empty (null / 0 / false /
        if (!is_string($value) && !empty($value)) {
            $type = gettype($value);
            $context->addError("The option %path% expects Date, $type given.", Message::PATTERN_MISMATCH);
            return null;
        }

        if (!$this->nullable && empty($value)) {
            $context->addError('The option %path% expects not-nullable Date, nothing given.', Message::PATTERN_MISMATCH);
            return null;
        }

        $normalized = DateTimeImmutable::createFromFormat($this->format, $value, $this->timeZone);
        if (!$normalized instanceof DateTimeImmutable) {
            $context->addError("The option %path% expects Date to match pattern '$this->format', '$value' given.", Message::PATTERN_MISMATCH);
            return null;
        }

        if($normalized->format($this->format) !== $value) {
            $context->addError("The option %path% expects Date to be valid Date format. Input Date is not the same as formatted date. Format:'$this->format' Input: '$value', Formatted: '{$normalized->format($this->format)}'.", Message::PATTERN_MISMATCH);
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
