<?php

declare(strict_types=1);

namespace Noirapi\Lib\Traits;

use ReflectionClass;
use ReflectionProperty;

trait SmartObject
{
    use \Nette\SmartObject;

    // We must use static objects to avoid messing with Model queries
    /** @var string[] */
    private static array $__smartObjectAttributeClasses; // phpcs:ignore
    /** @var ReflectionProperty [] */
    private static array $__properties; // phpcs:ignore

    public function __construct()
    {

        $this->_SmartObjectInit();
    }

    private function _SmartObjectInit(): void // phpcs:ignore
    {

        $reflection = new ReflectionClass($this);

        // Get Composer class name
        foreach (get_declared_classes() as $className) {
            if (str_starts_with($className, 'ComposerAutoloaderInit')) {

                /** @noinspection PhpUndefinedMethodInspection */
                foreach ($className::getLoader()->getClassMap() as $class => $path) {
                    if (str_starts_with($class, 'Noirapi\Lib\Attributes')) {
                        self::$__smartObjectAttributeClasses[] = $class;
                    } elseif (str_starts_with($class, 'App\Lib\Attributes')) {
                        self::$__smartObjectAttributeClasses[] = $class;
                    }
                }
            }
        }

        foreach ($reflection->getProperties() as $property) {
            if (count(self::$__smartObjectAttributeClasses) > 0) {
                foreach ($property->getAttributes() as $attribute) {
                    $name = $attribute->getName();
                    if (in_array($name, self::$__smartObjectAttributeClasses, true)) {
                        $name = $property->getName();
                        /** @phpstan-ignore property.dynamicName */
                        unset($this->$name);
                        /** @psalm-suppress UndefinedMethod */
                        self::$__properties[$name] = $property;
                    }
                }
            }
        }
    }

    /**
     * @param string $name
     * @return void
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function __get(string $name)
    {
        /** @psalm-suppress UndefinedMethod */
        if (isset(self::$__properties[$name])) {
            foreach (self::$__smartObjectAttributeClasses as $attributeClass) {
                if (self::$__properties[$name]->getAttributes($attributeClass)) {
                    $instance = self::$__properties[$name]->getAttributes($attributeClass)[0]->newInstance();
                    $args = [];

                    foreach ($instance->args as $arg) {
                        if (property_exists($this, $arg)) {
                            /** @phpstan-ignore property.dynamicName */
                            if ($this->{$arg} === null) {
                                return null;
                            }
                            /** @phpstan-ignore property.dynamicName */
                            $args[] = $this->{$arg};
                        }
                    }

                    if ($instance->isStatic) {
                        /** @phpstan-ignore property.dynamicName */
                        return $instance->className::{$instance->methodName}(...$args);
                    }
                    /** @phpstan-ignore property.dynamicName */
                    return new $instance->className()->{$instance->methodName}(...$args);
                }
            }
        }

        // Fallthrough and handle it by Nette's SmartObject
        /** @phpstan-ignore property.dynamicName */
        return $this->$name;
    }
}
