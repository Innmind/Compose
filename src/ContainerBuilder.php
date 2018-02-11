<?php
declare(strict_types = 1);

namespace Innmind\Compose;

use Innmind\Url\PathInterface;
use Innmind\Immutable\MapInterface;
use Psr\Container\ContainerInterface;

interface ContainerBuilder
{
    /**
     * @param MapInterface<string, mixed> $arguments
     */
    public function __invoke(
        PathInterface $definition,
        MapInterface $arguments
    ): ContainerInterface;
}
