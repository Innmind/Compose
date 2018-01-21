<?php
declare(strict_types = 1);

namespace Fixture\Innmind\Compose\Stack;

use Fixture\Innmind\Compose\Stack;

final class Low implements Stack
{
    public function __invoke(): string
    {
        return 'low';
    }
}
