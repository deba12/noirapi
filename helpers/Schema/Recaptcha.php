<?php
/**
 * @noinspection ContractViolationInspection
 * @noinspection PhpUnused
 */
declare(strict_types=1);

namespace noirapi\helpers\Schema;

use JsonException;
use Nette\Schema\Context;
use Nette\Schema\Message;
use Nette\Schema\Schema;

/** @psalm-api  */
class Recaptcha implements Schema {

    private bool $required = false;
    private bool $nullable = false;

    private string $secret;
    private string $ip;

    /**
     * @param bool $state
     * @return $this
     */
    public function required(bool $state = true): self {

        $this->required = $state;
        return $this;

    }

    /**
     * @param bool $state
     * @return $this
     */
    public function nullable(bool $state = true): self {

        $this->nullable = $state;
        return $this;

    }

    /**
     * @param string $secret
     * @return $this
     */
    public function secret(string $secret): self {

        $this->secret = $secret;
        return $this;

    }

    public function ip(string $ip): self {

        $this->ip = $ip;
        return $this;

    }

    /**
     * @param $value
     * @param Context $context
     * @return bool|null
     * @psalm-suppress MissingParamType
     */
    public function normalize($value, Context $context): ?bool {

        if($this->nullable && empty($value)) {
            return null;
        }

        if(!$this->nullable && empty($value)) {
            /** @noinspection UnusedFunctionResultInspection */
            $context->addError('The option %path% requires valid recaptcha response', Message::PATTERN_MISMATCH);
            return false;
        }

        if(empty($this->secret)) {
            /** @noinspection UnusedFunctionResultInspection */
            $context->addError('The option %path% requires valid recaptcha secret', Message::PATTERN_MISMATCH);
            return false;
        }

        if(!$this->verify($value, $this->secret)) {
            /** @noinspection UnusedFunctionResultInspection */
            $context->addError('Captcha verification failed', Message::PATTERN_MISMATCH);
            return false;
        }

        return true;

    }

    /**
     * @param $value
     * @param $base
     * @return mixed
     * @psalm-suppress MissingParamType
     */
    public function merge($value, $base): mixed {

        return $value;

    }

    /**
     * @param $value
     * @param Context $context
     * @return mixed
     * @psalm-suppress MissingParamType
     */
    public function complete($value, Context $context): mixed {

        return $value;

    }

    /**
     * @param Context $context
     * @return null
     */
    public function completeDefault(Context $context): null {

        if ($this->required) {
            /** @noinspection UnusedFunctionResultInspection */
            $context->addError('The mandatory option %path% is missing.', Message::MISSING_ITEM);
        }

        return null;

    }

    /**
     * @param string $code
     * @param string $secret
     * @return bool
     */
    private function verify(string $code, string $secret): bool {

        $post = [
            'secret'    => $secret,
            'response'  => $code
        ];

        if(!empty($this->ip)) {
            $post['remoteip'] = $this->ip;
        }

        $opts = [
            'http' =>
                [
                    'method'  => 'POST',
                    'header'  => 'Content-type: application/x-www-form-urlencoded',
                    'content' => http_build_query($post)
                ]
        ];

        $context  = stream_context_create($opts);
        $result = file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $context);

        try {
            $res = json_decode($result, false, 512, JSON_THROW_ON_ERROR);
            return isset($res->success);
        } catch (JsonException) {
            return false;
        }

    }

}
