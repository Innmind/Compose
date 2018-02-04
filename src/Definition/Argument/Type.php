<?php
declare(strict_types = 1);

namespace Innmind\Compose\Definition\Argument;

use Innmind\Immutable\Str;

interface Type
{
    /**
     * @param mixed $value
     */
    public function accepts($value): bool;

    /**
     * @throws ValueNotSupported
     */
    public static function fromString(Str $value): self;

    /**
     * Return the type handled
     */
    public function __toString(): string;
}
