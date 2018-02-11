<?php
declare(strict_types = 1);

namespace Innmind\Compose\Compilation\Service;

use Innmind\Compose\Compilation\MethodName;

interface Argument
{
    public function method(): MethodName;
    public function __toString(): string;
}
