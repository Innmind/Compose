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
    private $definitions;

    public function __construct(Definitions $definitions)
    {
        $this->definitions = $definitions;
    }

    /**
     * {@inheritdoc}
     */
    public function get($id): object
    {
        if (!$this->has($id)) {
            throw new NotFound($id);
        }

        return $this->definitions->build(new Name($id));
    }

    /**
     * {@inheritdoc}
     */
    public function has($id): bool
    {
        $name = new Name($id);

        if (!$this->definitions->has($name)) {
            return false;
        }

        $definition = $this
            ->definitions
            ->get($name);

        if (!$definition->exposed()) {
            return false;
        }

        return $definition->isExposedAs($name);
    }
}
