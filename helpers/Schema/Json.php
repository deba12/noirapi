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

use JsonException;
use Nette\Schema\Context;
use Nette\Schema\Message;
use Nette\Schema\Schema;

/** @psalm-api  */
class Json implements Schema {

    /** @var bool */
    private bool $required = false;

    /** @var bool */
    private bool $nullable = false;

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

        if(!$this->nullable && empty($value)) {
            /** @noinspection UnusedFunctionResultInspection */
            $context->addError("The option %path% requires valid json", Message::PATTERN_MISMATCH);
            return false;
        }

        try {
            $ret = json_decode($value, false, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            /** @noinspection UnusedFunctionResultInspection */
            $context->addError("The option %path% requires valid json. " . $e->getMessage(), Message::PATTERN_MISMATCH);
            return false;
        }

        return $ret;

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
