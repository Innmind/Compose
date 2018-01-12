<?php
declare(strict_types = 1);

namespace Innmind\Compose;

use Innmind\Compose\{
    Definition\Name,
    Definition\Service,
    Definition\Service\Argument,
    Exception\ArgumentNotProvided,
    Exception\ArgumentNotDefined,
    Exception\AtLeastOneServiceMustBeExposed,
    Exception\CircularDependency
};
use Innmind\Immutable\{
    Sequence,
    MapInterface,
    Map,
    Pair,
    Stream
};

final class Definitions
{
    private $definitions;
    private $exposed;
    private $arguments;
    private $building;

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
        $this->exposed = $this
            ->definitions
            ->filter(static function(string $name, Service $definition): bool {
                return $definition->exposed();
            })
            ->map(static function(string $name, Service $definition): Pair {
                return new Pair(
                    (string) $definition->exposedAs(),
                    $definition
                );
            });

        if ($this->exposed->size() === 0) {
            throw new AtLeastOneServiceMustBeExposed;
        }

        $this->building = Stream::of('string');
    }

    /**
     * @param MapInterface<string, mixed> $arguments
     */
    public function inject(MapInterface $arguments): self
    {
        $self = clone $this;
        $self->arguments = $self->arguments->bind($arguments);
        $self->building = $self->building->clear();

        return $self;
    }

    public function build(Name $name): object
    {
        try {
            if ($this->building->contains((string) $name)) {
                throw new CircularDependency(
                    (string) $this
                        ->building
                        ->add((string) $name)
                        ->join(' -> ')
                );
            }

            $this->building = $this->building->add((string) $name);

            $service = $this->get($name)->build($this);

            $this->building = $this->building->dropEnd(1);
        } catch (\Throwable $e) {
            $this->building = $this->building->clear();

            throw $e;
        }

        return $service;
    }

    public function arguments(): Arguments
    {
        return $this->arguments;
    }

    public function has(Name $name): bool
    {
        if ($this->definitions->contains((string) $name)) {
            return true;
        }

        return $this->exposed->contains((string) $name);
    }

    public function get(Name $name): Service
    {
        try {
            return $this->definitions->get((string) $name);
        } catch (\Exception $e) {
            return $this->exposed->get((string) $name);
        }
    }
}
