<?php
declare(strict_types=1);

namespace noirapi\helpers;

use Nette\Neon\Exception;
use Nette\Neon\Neon;

class EasyTranslator
{

    private static array $cache = [];
    private string $file;
    public function __construct(
        private readonly string $language,
        private readonly string $controller,
        private readonly string $function
    ) {
        $this->file = APPROOT . '/translations/' . $this->language . '.neon';
    }

    /**
     * @param string $message
     * @param string|null $key
     * @return string
     * @throws Exception
     */
    public function translate(string $message, ?string $key = null): string {

        // Condition is like /en,
        if($message === '/') {
            return '/' . $this->language;
        }

        // Condition for local url, we prepend the language
        if(str_starts_with($message, '/')) {
            return '/' . $this->language . $message;
        }

        if(isset(self::$cache[$this->language])) {
            return $this->lookup(self::$cache[$this->language], $message, $key);
        }

        if(is_file($this->file)) {
            self::$cache[$this->language] = Neon::decodeFile($this->file);
            return $this->lookup(self::$cache[$this->language], $message, $key);
        }

        return $message;

    }

    private function lookup(array $translations, string $message, ?string $key = null): string {

        if($key !== null) {

            if(str_contains($key, '.')) {

                $check = $translations;
                foreach(explode('.', $key) as $k) {
                    if(isset($check[$k])) {
                        $check = $check[$k];
                    }
                }

                if(is_string($check)) {
                    return $check;
                }

            }

            if (isset($translations[$this->controller][$this->function][$key])) {
                return $translations[$this->controller][$this->function][$key];
            }

            if (isset($translations[$this->controller][$key])) {
                return $translations[$this->controller][$key];
            }

            if(isset($translations[$key])) {
                return $translations[$key];
            }
        }

        return $translations[ $message ] ?? $message;

    }

}
