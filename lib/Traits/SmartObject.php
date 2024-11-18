<?php

declare(strict_types=1);

namespace noirapi\lib\Traits;

use ReflectionAttribute;
use ReflectionClass;

trait SmartObject
{
    use \Nette\SmartObject;

    private ReflectionClass $_reflection;
    /** @var string[] */
    private array $_smartObjectAttributeClasses;
    /** @var ReflectionAttribute[] */
    private array $_properties;

    public function __construct()
    {

        $this->_reflection = new ReflectionClass($this);

        // Get Composer class name
        foreach (get_declared_classes() as $className) {

            if (str_starts_with($className, 'ComposerAutoloaderInit')) {

                /** @noinspection PhpUndefinedMethodInspection */
                foreach($className::getLoader()->getClassMap() as $class => $path) {
                    if(str_starts_with($class, 'noirapi\lib\Attributes')) {
                        $this->_smartObjectAttributeClasses[] = $class;
                    } elseif(str_starts_with($class, 'app\lib\Attributes')) {
                        $this->_smartObjectAttributeClasses[] = $class;
                    }
                }
            }

        }

        foreach ($this->_reflection->getProperties() as $property) {

            if(count($this->_smartObjectAttributeClasses) > 0) {

                foreach ($property->getAttributes() as $attribute) {

                    $name = $attribute->getName();
                    if(in_array($name, $this->_smartObjectAttributeClasses, true)) {

                        $name = $property->getName();
                        unset($this->$name);

                        $this->_properties[] = $property;
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

        foreach($this->_properties as $property) {

            if($property === $name) {

                foreach($this->_smartObjectAttributeClasses as $attributeClass) {

                    if($property->getAttributes($attributeClass)) {
                        $instance = $property->getAttributes($attributeClass)[0]->newInstance();
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

        }

        // Fallthrough
        return $this->$name;

    }
}
