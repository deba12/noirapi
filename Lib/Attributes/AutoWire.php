<?php

declare(strict_types=1);

namespace Noirapi\Lib\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class AutoWire
{
    public string|array|null $callable = null;

    public function __construct(string|array|null $callable)
    {
        $this->callable = $callable;
    }
}
