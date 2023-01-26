<?php
/** @noinspection PhpPropertyOnlyWrittenInspection */
declare(strict_types=1);

namespace noirapi\helpers;

use JsonException;

class RestMessage {

    private bool $status;
    private string $message;

    public static function new(bool $status, string|object|array $message): RestMessage {

        $static = new self();

        if(is_string($message)) {

            $static->message = $message;

        } elseif(is_object($message) || is_array($message)) {

            foreach($message as $key => $value) {

                if($key === 'status' || $key === 'message') {
                    throw new \InvalidArgumentException('Invalid key in message object: ' . $key);
                }

                $static->$key = $value;
            }

        }

        $static->status = $status;

        return $static;

    }

    /**
     * @return string
     * @throws JsonException
     */
    public function toJson(): string {
        return json_encode($this->message, JSON_THROW_ON_ERROR|JSON_PRETTY_PRINT);
    }

}
