<?php

/** @noinspection ContractViolationInspection */

declare(strict_types=1);

namespace Noirapi\Helpers\Schema;

use Nette\Schema\Context;
use Nette\Schema\Message;
use Nette\Schema\Schema;

use function is_string;

/** @psalm-api  */
class Numeric implements Schema
{
    private bool $required = false;
    private bool $nullable = false;
    /** @var array{?int, ?int} */
    private array $range = [null, null];

    public function required(bool $state = true): self
    {
        $this->required = $state;

        return $this;
    }

    public function nullable(bool $state = true): self
    {
        $this->nullable = $state;

        return $this;
    }

    /**
     * @param int|string $min
     * @return $this
     */
    public function min(int|string $min): Numeric
    {
        $this->range[0] = is_string($min) ? (int)$min : $min;

        return $this;
    }

    /**
     * @param int|string $max
     * @return $this
     */
    public function max(int|string $max): Numeric
    {
        $this->range[1] = is_string($max) ? (int)$max : $max;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function normalize(mixed $value, Context $context): int|null|false
    {

        /** @noinspection TypeUnsafeComparisonInspection */
        /** @noinspection DuplicatedCode */
        /** @phpstan-ignore notEqual.notAllowed */
        if ($this->nullable && (empty($value) && $value != '0')) {
            return null;
        }

        /** @noinspection TypeUnsafeComparisonInspection */
        /** @phpstan-ignore notEqual.notAllowed */
        if ($this->required && (empty($value) && $value != '0')) {
            /** @noinspection UnusedFunctionResultInspection */
            $context->addError('The mandatory option %path% is empty.', Message::MissingItem);

            return null;
        }

        if (is_string($value) && ! preg_match('/\d+/', $value)) {
            /** @noinspection UnusedFunctionResultInspection */
            $context->addError('The mandatory option %path% is string but not number.', Message::PatternMismatch);

            return null;
        }
        $value = (int) $value;

        if (! $this->isInRange($value, $this->range)) {
            /** @noinspection UnusedFunctionResultInspection */
            $context->addError(
                'The %label% %path% expects to be in range %expected%, %value% given.',
                Message::ValueOutOfRange,
                ['value' => $value, 'expected' => implode('..', $this->range)]
            );

            return false;
        }

        return $value;
    }

    /**
     * @inheritDoc
     */
    public function merge(mixed $value, mixed $base): mixed
    {
        return $value;
    }

    /**
     * @inheritDoc
     */
    public function complete(mixed $value, Context $context)
    {
        return $value;
    }

    /**
     * @inheritDoc
     */
    public function completeDefault(Context $context)
    {
        if ($this->required) {
            /** @noinspection UnusedFunctionResultInspection */
            $context->addError('The mandatory option %path% is missing.', Message::MissingItem);
        }

        return null;
    }

    /**
     * @param int|string $value
     * @param array{?int, ?int} $range
     * @return bool
     */
    private function isInRange(int|string $value, array $range): bool
    {
        $value = (int) $value;

        return ($range[0] === null || $value >= $range[0])
            && ($range[1] === null || $value <= $range[1]);
    }
}
