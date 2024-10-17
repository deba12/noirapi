<?php
declare(strict_types=1);

namespace noirapi\lib\Traits;

use noirapi\lib\Attributes\LazyModel;
use Nette\SmartObject;
use ReflectionClass;

trait LazyModelInit
{

    use SmartObject;

    private ReflectionClass $reflection;

    public function __construct()
    {

        $this->reflection = new ReflectionClass($this);

        foreach($this->reflection->getProperties() as $property) {
            // Sets Value to null if property has LazyModel Attribute
            if($property->getAttributes(LazyModel::class)) {
                $name = $property->getName();
                unset($this->$name);
            }
        }

    }


    /**
     * @param string $name
     * @return void
     */
    public function __get(string $name)
    {

        foreach($this->reflection->getProperties() as $property) {
            // Sets Value to null if property has LazyModel Attribute
            if($property->name === $name  && $property->getAttributes(LazyModel::class)) {
                $instance = $property->getAttributes(LazyModel::class)[0]->newInstance();
                $args = [];

                foreach($instance->args as $arg) {
                    if(property_exists($this, $arg)) {
                        if($this->$arg === null) {
                            return null;
                        }
                        $args[] = $this->$arg;
                        break;
                    }
                }

                return $instance->model->{$instance->method}(...$args);

            }
        }

        return $this->$name;

    }

}
