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

/** @psalm-api  */
class Ips implements Schema
{

    private bool $required = false;
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

    /**
     * @param mixed $value
     * @param Context $context
     * @return array|null
     */
    public function normalize(mixed $value, Context $context): ?array
    {

        if($this->required && empty($value) && ! $this->nullable) {
            /** @noinspection UnusedFunctionResultInspection */
            $context->addError('The mandatory option %path% is empty.', Message::MISSING_ITEM);

            return null;
        }

        $parsed = preg_split('/(\r\n|\n|\r)/', $value);
        $ips = [];
        foreach($parsed as $ip) {
            if(empty(trim($ip))) {
                continue;
            }
            if(! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                /** @noinspection UnusedFunctionResultInspection */
                $context->addError("Value: ($ip) is not valid ipv4 address", Message::TYPE_MISMATCH);

                return null;
            }
            $ips[] = $ip;
        }

        return $ips;

    }

    /**
     * @param mixed $value
     * @param mixed $base
     * @return mixed
     */
    public function merge(mixed $value, mixed $base): mixed
    {
        return $value;
    }

    /**
     * @param mixed $value
     * @param Context $context
     * @return mixed
     */
    public function complete(mixed $value, Context $context): mixed
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
