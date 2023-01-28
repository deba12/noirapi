<?php
/** @noinspection PhpPropertyOnlyWrittenInspection */
declare(strict_types=1);

namespace noirapi\helpers;

use InvalidArgumentException;
use JsonException;

class RestMessage {

    public bool $status;
    public string $message;
    public ?string $next;

    public static function new(bool $status, string|object|array $message, ?string $next = null): RestMessage {

        $static = new self();

        if(is_string($message)) {

            $static->message = $message;

        } elseif(is_object($message) || is_array($message)) {

            foreach($message as $key => $value) {

                /** @noinspection InArrayCanBeUsedInspection */
                if($key === 'status' || $key === 'message' || $key === 'next') {
                    throw new InvalidArgumentException('Invalid key in message object: ' . $key);
                }

                $static->$key = $value;
            }

        }

        $static->status = $status;
        $static->next = $next;

        return $static;

    }

    /**
     * @return string
     * @throws JsonException
     */
    public function toJson(): string {
        return json_encode(get_object_vars($this), JSON_THROW_ON_ERROR|JSON_PRETTY_PRINT);
    }

}
