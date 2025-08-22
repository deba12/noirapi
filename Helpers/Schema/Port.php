<?php

/** @noinspection ContractViolationInspection */

declare(strict_types=1);

namespace Noirapi\Helpers\Schema;

use Nette\Schema\Context;
use Nette\Schema\Message;
use Nette\Schema\Schema;

use Override;
use function is_string;

/** @psalm-api  */
class Port implements Schema
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
     * @param int $min
     * @return $this
     */
    public function min(int $min): Port
    {
        $this->range[0] = $min;

        return $this;
    }

    /**
     * @param int $max
     * @return $this
     */
    public function max(int $max): Port
    {
        $this->range[1] = $max;

        return $this;
    }

    /**
     * @inheritDoc
     * @psalm-suppress MissingParamType
     */
    #[Override]
    public function normalize($value, Context $context)
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
     * @psalm-suppress MissingParamType
     */
    #[Override]
    public function merge($value, $base)
    {
        return $value;
    }

    /**
     * @inheritDoc
     * @psalm-suppress MissingParamType
     */
    #[Override]
    public function complete($value, Context $context)
    {
        return $value;
    }

    /**
     * @inheritDoc
     *
     */
    #[Override]
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
