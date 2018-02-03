<?php
declare(strict_types = 1);

namespace Fixture\Innmind\Compose\Stack;

use Fixture\Innmind\Compose\Stack;

final class Middle implements Stack
{
    private $stack;
    private $text;

    public function __construct(Stack $stack, string $text = 'middle')
    {
        $this->stack = $stack;
        $this->text = $text;
    }

    public function __invoke(): string
    {
        return sprintf(
            '%s|%s|%s',
            $this->text,
            ($this->stack)(),
            $this->text
        );
    }
}
