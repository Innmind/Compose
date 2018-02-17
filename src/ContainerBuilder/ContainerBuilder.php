<?php
declare(strict_types = 1);

namespace Innmind\Compose\ContainerBuilder;

use Innmind\Compose\{
    ContainerBuilder as ContainerBuilderInterface,
    Container,
    Loader
};
use Innmind\Url\PathInterface;
use Innmind\Immutable\MapInterface;
use Psr\Container\ContainerInterface;

final class ContainerBuilder implements ContainerBuilderInterface
{
    private $load;

    public function __construct(Loader $loader = null)
    {
        $this->load = $loader ?? new Loader\Yaml;
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
