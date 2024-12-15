<?php

declare(strict_types=1);

namespace Noirapi\Lib\Traits;

use ReflectionAttribute;
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
                        unset($this->$name);

                        self::$__properties[$name] = $property;
                    }
                }
            }
        }
    }

    /**
     * @param string $name
     * @return void
     */
    public function __get(string $name)
    {

        if (isset(self::$__properties[$name])) {
            foreach (self::$__smartObjectAttributeClasses as $attributeClass) {
                if (self::$__properties[$name]->getAttributes($attributeClass)) {
                    $instance = self::$__properties[$name]->getAttributes($attributeClass)[0]->newInstance();
                    $args = [];

                    foreach ($instance->args as $arg) {
                        if (property_exists($this, $arg)) {
                            if ($this->$arg === null) {
                                return null;
                            }
                            $args[] = $this->$arg;
                        }
                    }

                    return $instance->_class->{$instance->_method}(...$args);
                }
            }
        }

        // Fallthrough and handle it by Nette's SmartObject
        return $this->$name;
    }
}
