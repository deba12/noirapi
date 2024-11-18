<?php

declare(strict_types=1);

namespace noirapi\lib\Traits;

use ReflectionAttribute;
use ReflectionClass;

trait SmartObject
{
    use \Nette\SmartObject;

    /** @var string[] */
    private array $__smartObjectAttributeClasses;
    /** @var ReflectionAttribute[] */
    private array $__properties;

    public function __construct()
    {

        $this->_SmartObjectInit();

    }

    private function _SmartObjectInit(): void {

        $reflection = new ReflectionClass($this);

        // Get Composer class name
        foreach (get_declared_classes() as $className) {

            if (str_starts_with($className, 'ComposerAutoloaderInit')) {

                /** @noinspection PhpUndefinedMethodInspection */
                foreach($className::getLoader()->getClassMap() as $class => $path) {
                    /** @noinspection SpellCheckingInspection */
                    if(str_starts_with($class, 'noirapi\lib\Attributes')) {
                        $this->__smartObjectAttributeClasses[] = $class;
                    } elseif(str_starts_with($class, 'app\lib\Attributes')) {
                        $this->__smartObjectAttributeClasses[] = $class;
                    }
                }
            }

        }

        foreach ($reflection->getProperties() as $property) {

            if(count($this->__smartObjectAttributeClasses) > 0) {

                foreach ($property->getAttributes() as $attribute) {

                    $name = $attribute->getName();
                    if(in_array($name, $this->__smartObjectAttributeClasses, true)) {

                        $name = $property->getName();
                        unset($this->$name);

                        $this->__properties[$name] = $property;
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

        if(isset($this->__properties[$name])) {

            foreach($this->__smartObjectAttributeClasses as $attributeClass) {

                if($this->__properties[$name]->getAttributes($attributeClass)) {

                    $instance = $this->__properties[$name]->getAttributes($attributeClass)[0]->newInstance();
                    $args = [];

                    foreach($instance->args as $arg) {
                        if(property_exists($this, $arg)) {
                            if($this->$arg === null) {
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
