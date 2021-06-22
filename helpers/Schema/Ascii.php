<?php /** @noinspection ContractViolationInspection */
declare(strict_types=1);

namespace noirapi\helpers\Schema;

use Nette\Schema\Context;
use Nette\Schema\Message;
use Nette\Schema\Schema;

class Ascii implements Schema {

    /** @var bool */
    private $required = false;
    /** @var bool */
    private $nullable = false;

    public function required(bool $state = true): self {
        $this->required = $state;
        return $this;
    }

    public function nullable(bool $state = true): self {
        $this->nullable = $state;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function normalize($value, Context $context) {

        if($this->nullable && empty($value)) {
            return null;
        }

        if($this->required && empty($value)) {
            /** @noinspection UnusedFunctionResultInspection */
            $context->addError('The mandatory option %path% is empty.', Message::MISSING_ITEM);
            return null;
        }

        if(!is_string($value) || !preg_match('/[^\x20-\x7e]/', $value)) {
            /** @noinspection UnusedFunctionResultInspection */
            $context->addError('The mandatory option %path% have to be valid ascii string.', Message::PATTERN_MISMATCH);
            return null;
        }

        return $value;

    }

    /**
     * @inheritDoc
     */
    public function merge($value, $base) {
        return $value;
    }

    /**
     * @inheritDoc
     */
    public function complete($value, Context $context) {
        return $value;
    }

    /**
     * @inheritDoc
     */
    public function completeDefault(Context $context) {
        if ($this->required) {
            /** @noinspection UnusedFunctionResultInspection */
            $context->addError('The mandatory option %path% is missing.', Message::MISSING_ITEM);
        }
        return null;
    }

}
