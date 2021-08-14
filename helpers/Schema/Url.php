<?php
/**
 * @noinspection PhpMissingReturnTypeInspection
 * @noinspection PhpUnused
 * @noinspection UnknownInspectionInspection
 * @noinspection ReturnTypeCanBeDeclaredInspection
 * @noinspection ContractViolationInspection
 */
declare(strict_types=1);

namespace noirapi\helpers\Schema;

use Nette\Schema\Context;
use Nette\Schema\Message;
use Nette\Schema\Schema;

class Url implements Schema {

    /** @var bool */
    private $required = false;

    /** @var bool */
    private $nullable = false;

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

    public function normalize($value, Context $context)
    {

        if($this->nullable && empty($value)) {
            return null;
        }

        /** @noinspection BypassedUrlValidationInspection */
        return filter_var($value, FILTER_VALIDATE_URL);
    }

    public function merge($value, $base)
    {
        return $value;
    }

    public function complete($value, Context $context)
    {
        return $value;
    }

    /** @noinspection ReturnTypeCanBeDeclaredInspection */
    public function completeDefault(Context $context)
    {
        if ($this->required) {
            /** @noinspection UnusedFunctionResultInspection */
            $context->addError('The mandatory option %path% is missing.', Message::MISSING_ITEM);
        }
        return null;
    }

}
