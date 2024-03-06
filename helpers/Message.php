<?php
declare(strict_types=1);

namespace noirapi\helpers;
use Nette\Schema\ValidationException;

/** @psalm-api  */
class Message {

    public string $message;
    public string $type = 'info';
    public int $timeout_ms = 5000;
    public bool $html = false;

    /**
     * @param string $message
     * @param string|null $type
     * @return Message
     */
    public static function new(string $message, ?string $type = null): Message {

        $static = new self();

        $static->message = $message;
        if($type !== null) {
            $static->type = $type;
        }

        return $static;

    }

    public static function fromSchema(ValidationException $e, ?string $type = null): Message {

        return self::new(SchemaHelper::message($e), $type);

    }


    /**
     * @param int $timeout_ms
     * @return $this
     * @noinspection PhpUnused
     */
    public function timeout(int $timeout_ms): Message {
        $this->timeout_ms = $timeout_ms;
        return $this;
    }

    /**
     * @return $this
     */
    public function html(): Message {
        $this->html = true;
        return $this;
    }

    /**
     * @return $this
     */
    public function primary(): Message {
        $this->type = 'primary';
        return $this;
    }

    /**
     * @return $this
     */
    public function secondary(): Message {
        $this->type = 'secondary';
        return $this;
    }

    /**
     * @return $this
     * @noinspection PhpUnused
     */
    public function light(): Message {
        $this->type = 'light';
        return $this;
    }

    /**
     * @return $this
     * @noinspection PhpUnused
     */
    public function dark(): Message {
        $this->type = 'dark';
        return $this;
    }

    /**
     * @return $this
     */
    public function danger(): Message {
        $this->type = 'danger';
        return $this;
    }

    /**
     * @return $this
     */
    public function success(): Message {
        $this->type = 'success';
        return $this;
    }

    /**
     * @return $this
     */
    public function warning(): Message {
        $this->type = 'warning';
        return $this;
    }

    /**
     * @return $this
     */
    public function info(): Message {
        $this->type = 'info';
        return $this;
    }

}
