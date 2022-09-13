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

class Ip implements Schema {

    private bool $required = false;
    private bool $nullable = false;
    private string $from = 'string';
    private string $to = 'string';

    public function fromBin()
    {
        $this->from = 'bin';
        return $this;
    }

    public function fromString()
    {
        $this->from = 'string';
        return $this;
    }

    public function fromLong()
    {
        $this->from = 'long';
        return $this;
    }

    public function toBin()
    {
        $this->to = 'bin';
        return $this;
    }

    public function toString()
    {
        $this->to = 'string';
        return $this;
    }

    public function toLong()
    {
        $this->to = 'long';
        return $this;
    }

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

        // 0 is valid long ip address
        /** @noinspection TypeUnsafeComparisonInspection */
        if($this->nullable && (empty($value) && $value != '0')) {
            return null;
        }

        /** @noinspection TypeUnsafeComparisonInspection */
        if($this->required && (empty($value) && $value != '0')) {
            /** @noinspection UnusedFunctionResultInspection */
            $context->addError('The mandatory option %path% is empty.', Message::MISSING_ITEM);
            return null;
        }

        switch($this->from)
        {
            case 'string':
                $from = $value;
                break;

            case 'long':
                if(!ctype_digit($value)) {
                    /** @noinspection UnusedFunctionResultInspection */
                    $context->addError('The option %path% is not valid integer.', Message::TYPE_MISMATCH);
                    return null;
                }

                $value = (int) $value;
                $from = long2ip($value);
                if($value !== ip2long($from)) {
                    /** @noinspection UnusedFunctionResultInspection */
                    $context->addError('The option %path% is not valid (long) ipv4 address.', Message::TYPE_MISMATCH);
                    return null;
                }
                break;

            case 'bin':
                $from = inet_ntop($value);
                if($value !== inet_pton($from)) {
                    /** @noinspection UnusedFunctionResultInspection */
                    $context->addError('The option %path% is not valid (binary) ip address.', Message::TYPE_MISMATCH);
                    return null;
                }
                break;
        }

        if(empty($from)) {
            /** @noinspection UnusedFunctionResultInspection */
            /** @noinspection PhpUndefinedVariableInspection */
            $context->addError("The option %path% expects valid ip address. ('$from') given", Message::TYPE_MISMATCH);
            return null;
        }

        if(!filter_var($from, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4|FILTER_FLAG_IPV6)) {
            /** @noinspection UnusedFunctionResultInspection */
            $context->addError("The option %path% expects valid ip (ipv4|ipv6) address. ('$from') given", Message::TYPE_MISMATCH);
            return null;
        }

        switch($this->to)
        {
            case 'string':
                $to = $from;
                break;

            case 'long':

                if(str_contains($from, ':')) {
                    /** @noinspection UnusedFunctionResultInspection */
                    $context->addError("The option %path% unable to convert ipv6 address to long", Message::TYPE_MISMATCH);
                    return null;
                }
                $to = ip2long($from);
                break;

            case 'bin':
                $to = inet_pton($from);
                break;

        }

        /** @noinspection PhpUndefinedVariableInspection */
        if(empty($to) && $to !== 0) {
            /** @noinspection UnusedFunctionResultInspection */
            $context->addError("The option %path% unable to produce valid ip address", Message::TYPE_MISMATCH);
            return null;
        }

        return $to;

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
