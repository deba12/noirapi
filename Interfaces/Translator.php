<?php
declare(strict_types=1);

namespace noirapi\interfaces;

interface Translator
{

    /**
     * @param string $message
     * @param string|null $key
     * @param mixed ...$args
     * @return string
     */
    public function translate(string $message, ?string $key = null, mixed ...$args): string;

}
