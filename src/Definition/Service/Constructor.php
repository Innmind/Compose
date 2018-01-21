<?php
declare(strict_types = 1);

namespace Innmind\Compose\Definition\Service;

use Innmind\Immutable\Str;

interface Constructor
{
    /**
     * @throws ValueNotSupported
     */
    public static function fromString(Str $value): self;

    /**
     * @param mixed $arguments
     */
    public function __invoke(...$arguments): object;
}
