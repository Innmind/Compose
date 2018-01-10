<?php
declare(strict_types = 1);

namespace Innmind\Compose\Definition\Service;

use Innmind\Compose\Definitions;
use Innmind\Immutable\StreamInterface;

interface Argument
{
    /**
     * @param mixed $value
     *
     * @throws ValueNotSupported
     */
    public static function fromValue($value): self;

    /**
     * @param StreamInterface<mixed> $built
     *
     * @return StreamInterface<mixed>
     */
    public function resolve(
        StreamInterface $built,
        Definitions $definitions
    ): StreamInterface;
}
