<?php
/** @noinspection PhpMultipleClassDeclarationsInspection */
declare(strict_types=1);

namespace noirapi\lib\Attributes;

use Attribute;
use RuntimeException;

#[Attribute(Attribute::TARGET_PROPERTY)]
class LazyModel
{

    public object $model;
    public string $method;
    public mixed $args;

    public function __construct(string $class, string $method, ...$args)
    {

        if(class_exists($class)) {
            $this->model = new $class;
        } else {
            throw new RuntimeException("Class $class does not exist");
        }

        if(method_exists($this->model, $method)) {
            $this->method = $method;
        } else {
            throw new RuntimeException("Method $method does not exist in $class");
        }

        $this->args = $args;

    }

}
