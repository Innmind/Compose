<?php
declare(strict_types = 1);

namespace Innmind\Compose\Definition\Argument\Type;

use Innmind\Compose\Definition\Argument\Type;
use Innmind\Immutable\MapInterface;

final class Map implements Type
{
    private $key;
    private $value;

    public function __construct(string $key, string $value)
    {
        $this->key = $key;
        $this->value = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function accepts($value): bool
    {
        if (!$value instanceof MapInterface) {
            return false;
        }

        return (string) $value->keyType() === $this->key &&
            (string) $value->valueType() === $this->value;
    }
}
