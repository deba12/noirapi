<?php
/** @noinspection PhpUnused */

declare(strict_types=1);

namespace Noirapi\Lib\Attributes;

use Attribute;

/** @psalm-api */
#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class NotFound
{
    public string $message;
    public int $status;


    public function __construct(string $message, int $status = 301)
    {
        $this->message = $message;
        $this->status = $status;
    }
}
