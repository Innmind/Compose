<?php
declare(strict_types = 1);

namespace Innmind\Compose\Definition\Argument\Type;

use Innmind\Compose\Definition\Argument\Type;
use Innmind\Immutable\StreamInterface;

final class Stream implements Type
{
    private $type;

    public function __construct(string $type)
    {
        $this->type = $type;
    }

    /**
     * {@inheritdoc}
     */
    public function accepts($value): bool
    {
        if (!$value instanceof StreamInterface) {
            return false;
        }

        return (string) $value->type() === $this->type;
    }
}
