<?php
declare(strict_types=1);

namespace noirapi\helpers;

class DummyTranslator
{

    /**
     * @param string $message
     * @param string|null $key
     * @return string
     */
    public function translate(string $message, ?string $key = null): string {
        return $message;
    }

}
