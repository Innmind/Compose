<?php
declare(strict_types = 1);

namespace Innmind\Compose\Definition\Argument\Type;

use Innmind\Compose\Definition\Argument\Type;
use Innmind\Immutable\SequenceInterface;

final class Sequence implements Type
{
    /**
     * {@inheritdoc}
     */
    public function accepts($value): bool
    {
        return $value instanceof SequenceInterface;
    }
}
