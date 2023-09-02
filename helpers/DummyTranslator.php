<?php
declare(strict_types=1);

namespace noirapi\helpers;

use noirapi\interfaces\Translator;

class DummyTranslator implements Translator
{

    /**
     * @param string $message
     * @param string|null $key
     * @return string
     */
    public function translate(string $message, ?string $key = null, ...$args): string {
        return $message;
    }

}
