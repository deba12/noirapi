<?php
declare(strict_types=1);

namespace noirapi\helpers;
use JetBrains\PhpStorm\Pure;

class Message {

    public string $message;
    public string $type;
    public int $timeout_ms = 5000;
    public bool $html = false;

    /**
     * @param string $message
     * @param string $type
     * @return Message
     */
    #[Pure]
    public static function new(string $message, string $type = 'info'): Message {

        $static = new self();

        $static->message = $message;
        $static->type = $type;

        return $static;

    }

    public function timeout(int $timeout_ms): Message {
        $this->timeout_ms = $timeout_ms;
        return $this;
    }

    public function html(): Message {
        $this->html = true;
        return $this;
    }

}
