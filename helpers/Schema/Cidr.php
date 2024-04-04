<?php
/** @noinspection ContractViolationInspection */
declare(strict_types=1);

namespace noirapi\helpers\Schema;

use Nette\Schema\Context;
use Nette\Schema\Schema;
use Nette\Schema\Message;

/** @psalm-api  */
class Cidr implements Schema {

    private bool $required = false;
    private bool $nullable = false;
    private bool $multiple;

    public function __construct(bool $multiple) {
        $this->multiple = $multiple;
    }

    public function required(bool $state = true): self {
        $this->required = $state;
        return $this;
    }

    public function nullable(): self {
        $this->nullable = true;
        return $this;
    }

    public function normalize($value, Context $context) {

        if($this->required && empty($value)) {
            /** @noinspection UnusedFunctionResultInspection */
            $context->addError('The mandatory option %path% is empty.', Message::MISSING_ITEM);
            return null;
        }

        if($this->nullable && empty($value)) {
            return null;
        }

        if($this->multiple) {
            $parsed = preg_split('/(\r\n|\n|\r)/', $value);
            $cidrs = [];

            foreach($parsed as $cidr) {
                if(empty(trim($cidr))) {
                    continue;
                }
                if(!$this->validateCidr($cidr)) {
                    /** @noinspection UnusedFunctionResultInspection */
                    $context->addError("Value: ($cidr) is not valid ipv4 or ipv6 cidr", Message::TYPE_MISMATCH);
                    return null;
                }
                $cidrs[] = $cidr;
            }
            return $cidrs;
        }

        if($this->validateCidr($value)) {
            /** @noinspection UnusedFunctionResultInspection */
            $context->addError("Value: ($value) is not valid ipv4 or ipv6 cidr", Message::TYPE_MISMATCH);
            return null;
        }

        return $value;

    }

    /**
     * @param $value
     * @param $base
     * @return mixed
     */
    public function merge($value, $base): mixed {
        return $value;
    }

    /**
     * @param $value
     * @param Context $context
     * @return mixed
     */
    public function complete($value, Context $context): mixed {
        return $value;
    }


    /**
     * @param Context $context
     * @return null
     * @noinspection ReturnTypeCanBeDeclaredInspection
     */
    public function completeDefault(Context $context) {
        if ($this->required) {
            /** @noinspection UnusedFunctionResultInspection */
            $context->addError('The mandatory option %path% is missing.', Message::MISSING_ITEM);
        }
        return null;
    }

    /**
     * @param string $cidr
     * @return bool
     */
    private function validateCidr(string $cidr): bool {

        [$ip, $netmask] = explode('/', $cidr);

        if($netmask < 0) {
            return false;
        }

        if(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $netmask <= 32;
        }

        if(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return $netmask <= 128;
        }

        return false;

    }

}
