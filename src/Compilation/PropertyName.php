<?php
declare(strict_types = 1);

namespace Innmind\Compose\Compilation;

use Innmind\Compose\Definition\Name;
use Innmind\Immutable\Str;

final class PropertyName
{
    private $value;

    public function __construct(Name $name)
    {
        $this->value = (string) Str::of((string) $name)
            ->replace('.', '_')
            ->camelize()
            ->lcfirst();
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
