<?php
declare(strict_types = 1);

namespace Innmind\Compose;

use Innmind\Compose\{
    Definition\Name,
    Exception\NotFound
};
use Innmind\Immutable\Map;
use Psr\Container\ContainerInterface;

final class Container implements ContainerInterface
{
    private $services;

    public function __construct(Services $services)
    {
        $this->services = $services;
    }

    /**
     * {@inheritdoc}
     */
    public function get($id): object
    {
        if (!$this->has($id)) {
            throw new NotFound($id);
        }

        return $this->services->build(new Name($id));
    }

    /**
     * {@inheritdoc}
     */
    public function has($id): bool
    {
        $name = new Name($id);

        if (!$this->services->has($name)) {
            return false;
        }

        $definition = $this
            ->services
            ->get($name);

        if (!$definition->exposed()) {
            return false;
        }

        return $definition->isExposedAs($name);
    }
}
