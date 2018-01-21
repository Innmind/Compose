<?php
declare(strict_types = 1);

namespace Fixture\Innmind\Compose\Stack;

use Fixture\Innmind\Compose\Stack;

final class High implements Stack
{
    private $stack;

    public function __construct(Stack $stack)
    {
        $this->stack = $stack;
    }

    public function __invoke(): string
    {
        return sprintf(
            'high|%s|high',
            ($this->stack)()
        );
    }
}
