<?php

declare(strict_types=1);

namespace Noirapi\Lib\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class AutoWire
{
    public ?string $getter_function = null;

    public function __construct(string $getter_function = 'get')
    {
        $this->getter_function = $getter_function;
    }
}
