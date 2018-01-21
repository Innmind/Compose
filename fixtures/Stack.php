<?php
declare(strict_types = 1);

namespace Fixture\Innmind\Compose;

interface Stack
{
    public function __invoke(): string;
}
