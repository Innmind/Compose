<?php
declare(strict_types = 1);

namespace Innmind\Compose\Definition\Service\Argument;

use Innmind\Compose\{
    Definition\Service\Argument,
    Definitions,
    Exception\ValueNotSupported,
    Exception\DecoratedArgumentCannotBeResolved
};
use Innmind\Immutable\StreamInterface;

final class Decorate implements Argument
{
    /**
     * {@inheritdoc}
     */
    public static function fromValue($value): Argument
    {
        if ($value !== '@decorated') {
            throw new ValueNotSupported;
        }

        return new self;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(
        StreamInterface $built,
        Definitions $definitions
    ): StreamInterface {
        throw new DecoratedArgumentCannotBeResolved;
    }
}
