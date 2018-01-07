<?php
declare(strict_types = 1);

namespace Innmind\Compose;

use Innmind\Compose\Definition\{
    Service,
    Name
};
use Innmind\Immutable\{
    Sequence,
    MapInterface,
    Map
};

final class Definitions
{
    private $definitions;
    private $arguments;

    public function __construct(Arguments $arguments, Service ...$definitions)
    {
        $this->arguments = $arguments;
        $this->definitions = Sequence::of(...$definitions)->reduce(
            new Map('string', Service::class),
            static function(Map $definitions, Service $definition): Map {
                return $definitions->put(
                    (string) $definition->name(),
                    $definition
                );
            }
        );
    }

    /**
     * @param MapInterface<string, mixed> $arguments
     */
    public function inject(MapInterface $arguments): self
    {
        $self = clone $this;
        $self->arguments = $self->arguments->bind($arguments);

        return $self;
    }

    public function arguments(): Arguments
    {
        return $this->arguments;
    }

    public function has(Name $name): bool
    {
        return $this->definitions->contains((string) $name);
    }

    public function get(Name $name): Service
    {
        return $this->definitions->get((string) $name);
    }
}
