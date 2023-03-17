<?php
declare(strict_types=1);

namespace noirapi\helpers;

use InvalidArgumentException;
use JsonException;
use function in_array;
use function is_array;
use function is_object;
use function is_string;

/**
 * @property string|null $message
 * @property bool $ok
 */
class RestMessage {

    private array $params = [];

    public static function new(bool $ok, string|object|array $message, string|null $next): RestMessage {

        $static = new self();
        $static->params['ok'] = $ok;
        $static->params['next'] = $next;

        if(is_string($message)) {

            $static->params['message'] = $message;

        } elseif(is_array($message) || is_object($message)) {

            foreach($message as $key => $value) {

                if(in_array($key, ['ok', 'message', 'next'], true)) {
                    throw new InvalidArgumentException('Invalid key in message object: ' . $key);
                }

                $static->params[$key] = $value;

            }

        }

        return $static;

    }

    /**
     * @return string
     * @throws JsonException
     */
    public function toJson(): string {

        return json_encode($this->params, JSON_THROW_ON_ERROR|JSON_PRETTY_PRINT);

    }

    /**
     * @param string $name
     * @return mixed|null
     */
    public function __get(string $name) {
        return $this->params[$name] ?? null;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function __set(string $name, mixed $value) {
        $this->params[$name] = $value;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function __isset(string $name): bool {
        return !empty($this->params[$name]);
    }

}
