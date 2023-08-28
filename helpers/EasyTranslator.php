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
     * @param mixed ...$args
     * @return string
     * @throws Exception
     */
    public function translate(string $message, ?string $key = null, ...$args): string {

        // Condition is like /en,
        if($message === '/') {
            return '/' . $this->language;
        }

        // Condition for local url, we prepend the language
        if(str_starts_with($message, '/')) {
            return '/' . $this->language . $message;
        }

        if(isset(self::$cache[$this->language])) {
            return $this->lookup(self::$cache[$this->language], $message, $key, ...$args);
        }

        if(is_file($this->file)) {
            self::$cache[$this->language] = Neon::decodeFile($this->file);
            return $this->lookup(self::$cache[$this->language], $message, $key, ...$args);
        }

        return $message;

    }

    /**
     * @param array $translations
     * @param string $message
     * @param string|null $key
     * @param ...$args
     * @return string
     */
    private function lookup(array $translations, string $message, ?string $key = null, ...$args): string {

        //TODO remake this, it's ugly
        if($key !== null) {

            if(str_contains($key, '.')) {

                $check = $translations;
                foreach(explode('.', $key) as $k) {
                    if(isset($check[$k])) {
                        $check = $check[$k];
                    }
                }

                if(is_string($check)) {
                    return str_contains($message, '%s') ? sprintf($check, ...$args) : $check;
                }

            }

            if (isset($translations[$this->controller][$this->function][$key])) {
                return str_contains($message, '%s') ? sprintf($translations[$this->controller][$this->function][$key], ...$args): $translations[$this->controller][$this->function][$key];
            }

            if (isset($translations[$this->controller][$key])) {
                return str_contains($message, '%s') ? sprintf($translations[$this->controller][$key], ...$args): $translations[$this->controller][$key];
            }

            if(isset($translations[$key])) {
                return str_contains($message, '%s') ? sprintf($translations[$key], ...$args) : $translations[$key];
            }

        }

        if(!empty($translations['message'])) {
            return str_contains($message, '%s') ? sprintf($translations['message'], ...$args) : $translations['message'];
        }

        return str_contains($message, '%s') ? sprintf($message, ...$args) : $message;

    }

}
