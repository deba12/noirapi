<?php
declare(strict_types=1);

namespace noirapi\helpers;

use function in_array;
use InvalidArgumentException;
use function is_string;
use JsonException;

/**
 * @property bool $ok
 * @property string|object|array $message
 * @property string|null $next
 * @property string|null $message_tag
 * @psalm-api
 */
class RestMessage
{

    private array $params = [];

    public static function new(bool $ok, string|object|array $message, string|null $next, string|null $message_tag): RestMessage
    {
        $static = new self();
        $static->params['ok'] = $ok;
        $static->params['next'] = $next;
        $static->params['message_tag'] = $message_tag;

        if(is_string($message)) {

            $static->params['message'] = $message;

        } else {

            foreach($message as $key => $value) {

                if(in_array($key, ['ok', 'message', 'next', 'message_tag'], true)) {
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
     * @noinspection PhpUnused
     */
    public function toJson(): string
    {
        return json_encode($this->params, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return $this->params;
    }

    /**
     * @param string $name
     * @return mixed|null
     */
    public function __get(string $name)
    {
        return $this->params[$name] ?? null;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function __set(string $name, mixed $value)
    {
        $this->params[$name] = $value;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function __isset(string $name): bool
    {
        return ! empty($this->params[$name]);
    }

}
