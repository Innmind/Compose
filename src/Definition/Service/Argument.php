<?php
declare(strict_types = 1);

namespace Innmind\Compose\Definition\Service;

use Innmind\Compose\Services;
use Innmind\Immutable\StreamInterface;

interface Argument
{
    /**
     * @param mixed $value
     *
     * @throws ValueNotSupported
     */
    public static function fromValue($value, Arguments $arguments): self;

    /**
     * @param StreamInterface<mixed> $built
     *
     * @return StreamInterface<mixed>
     */
    public function resolve(
        StreamInterface $built,
        Services $services
    ): StreamInterface;
}
