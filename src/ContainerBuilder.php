<?php
declare(strict_types = 1);

namespace Innmind\Compose;

use Innmind\Url\PathInterface;
use Innmind\Immutable\MapInterface;
use Psr\Container\ContainerInterface;

final class ContainerBuilder
{
    private $load;

    public function __construct(Loader $loader)
    {
        $this->load = $loader;
    }

    /**
     * @param MapInterface<string, mixed> $arguments
     */
    public function __invoke(
        PathInterface $definition,
        MapInterface $arguments
    ): ContainerInterface {
        $services = ($this->load)($definition);

        return new Container($services->inject($arguments));
    }
}
