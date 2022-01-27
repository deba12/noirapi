<?php
declare(strict_types=1);

namespace noirapi\helpers;
use Nette\Schema\ValidationException;

class SchemaHelper {

    public static array $messages;

    /**
     * @param array $messages
     * @return void
     */
    public static function set(array $messages): void {
        self::$messages = $messages;
    }

    /**
     * @param ValidationException $exceptions
     * @return string
     */
    public static function message(ValidationException $exceptions): string {

        foreach($exceptions->getMessageObjects() as $exception) {
            $path = array_shift($exception->path);
            if(isset(self::$messages[$path])) {
                return (string)self::$messages[$path];
            }
        }

        return $exceptions->getMessage();

    }

}
