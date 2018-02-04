<?php
declare(strict_types = 1);

namespace Innmind\Compose\Definition\Argument\Type;

use Innmind\Compose\{
    Definition\Argument\Type,
    Exception\ValueNotSupported
};
use Innmind\Immutable\{
    SequenceInterface,
    Str
};

final class Sequence implements Type
{
    /**
     * {@inheritdoc}
     */
    public function accepts($value): bool
    {
        return $value instanceof SequenceInterface;
    }

    /**
     * {@inheritdoc}
     */
    public static function fromString(Str $value): Type
    {
        if ((string) $value !== 'sequence') {
            throw new ValueNotSupported((string) $value);
        }

        return new self;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        return 'sequence';
    }
}
