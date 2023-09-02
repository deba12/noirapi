<?php
declare(strict_types=1);

namespace noirapi\interfaces;

interface Translator {

    public function translate(string $message, ?string $key = null, ...$args): string;

}
