<?php
declare(strict_types = 1);

namespace Innmind\Compose\Definition\Argument\Type;

use Innmind\Compose\Definition\Argument\Type;
use Innmind\Immutable\Str;

final class Instance implements Type
{
    private $class;

    public function __construct(string $class)
    {
        $this->class = $class;
    }

    /**
     * {@inheritdoc}
     */
    public function accepts($value): bool
    {
        return $value instanceof $this->class;
    }

    /**
     * {@inheritdoc}
     */
    public static function fromString(Str $value): Type
    {
        return new self((string) $value);
    }

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        return $this->class;
    }
}
