<?php
declare(strict_types = 1);

namespace Innmind\Compose\Compilation\Service;

interface Constructor
{
    public function __toString(): string;
}
