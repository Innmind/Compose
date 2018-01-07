<?php
declare(strict_types = 1);

namespace Innmind\Compose\Definition\Argument;

interface Type
{
    /**
     * @param mixed $value
     */
    public function accepts($value): bool;
}
