<?php
/**
 * @noinspection PhpUnused
 * @noinspection ContractViolationInspection
 * @noinspection UnknownInspectionInspection
 */
declare(strict_types=1);

namespace noirapi\helpers\Schema;


use Nette\Schema\Context;
use Nette\Schema\Message;
use Nette\Schema\Schema;

class Domain implements Schema {

    /** @var bool */
    private $wildcard = false;

    /** @var bool */
    private $required;

    /**
     * @param bool $required
     * @return $this
     */
    public function required(bool $required): Domain {
        $this->required = $required;
        return $this;
    }

    /**
     * @param bool $wildcard
     * @return $this
     */
    public function wildcard(bool $wildcard): Domain {
        $this->wildcard = $wildcard;
        return $this;
    }

    public function normalize($value, Context $context) {

        $value = trim($value);

        if($this->wildcard && $value[0] === '.') {
            $res = filter_var(substr($value, 1), FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME);

            if($res === false) {
                /** @noinspection UnusedFunctionResultInspection */
                $context->addError("The option %path% requires valid wildcard hostname", Message::PATTERN_MISMATCH);
                return null;
            }

            return '.' . $res;

        }

        $res = filter_var($value, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME);

        if($res === false) {
            /** @noinspection UnusedFunctionResultInspection */
            $context->addError("The option %path% requires valid hostname", Message::PATTERN_MISMATCH);
            return null;
        }

        return $res;

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