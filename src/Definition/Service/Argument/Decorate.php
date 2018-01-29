<?php
declare(strict_types = 1);

namespace Innmind\Compose\Definition\Service\Argument;

use Innmind\Compose\{
    Definition\Service\Argument,
    Definition\Service\Arguments,
    Services,
    Exception\ValueNotSupported,
    Exception\DecoratedArgumentCannotBeResolved
};
use Innmind\Immutable\StreamInterface;

final class Decorate implements Argument
{
    /**
     * {@inheritdoc}
     */
    public static function fromValue($value, Arguments $arguments): Argument
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
        Services $services
    ): StreamInterface {
        throw new DecoratedArgumentCannotBeResolved;
    }
}
