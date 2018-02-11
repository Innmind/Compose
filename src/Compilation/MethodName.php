<?php
declare(strict_types = 1);

namespace Innmind\Compose\Compilation;

use Innmind\Compose\Definition\Name;
use Innmind\Immutable\Str;

final class MethodName
{
    private $value;

    public function __construct(Name $name)
    {
        $this->value = (string) Str::of((string) $name)
            ->replace('.', '_')
            ->camelize()
            ->prepend('build');
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
