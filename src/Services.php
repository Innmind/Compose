<?php
declare(strict_types = 1);

namespace Innmind\Compose;

use Innmind\Compose\{
    Definition\Name,
    Definition\Service,
    Definition\Service\Argument,
    Definition\Service\Constructor,
    Definition\Dependency,
    Exception\ArgumentNotProvided,
    Exception\ArgumentNotDefined,
    Exception\CircularDependency
};
use Innmind\Immutable\{
    Sequence,
    MapInterface,
    Map,
    Pair,
    Stream,
    SetInterface,
    Set
};

final class Services
{
    private $definitions;
    private $exposed;
    private $arguments;
    private $dependencies;
    private $building;
    private $instances;

    public function __construct(
        Arguments $arguments,
        Dependencies $dependencies,
        Service ...$definitions
    ) {
        $this->arguments = $arguments;
        $this->dependencies = $dependencies;
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

        $this->building = Stream::of('string');
        $this->instances = new Map('string', 'object');
    }

    public function expose(Name $name, Name $as): self
    {
        $service = $this->get($name)->exposeAs($as);

        $self = clone $this;
        $self->definitions = $self->definitions->put(
            (string) $name,
            $service
        );
        $self->exposed = $self->exposed->put(
            (string) $as,
            $service
        );

        return $self;
    }

    public function stack(Name $name, Name $highest, Name $lower, Name ...$rest): self
    {
        $stack = Sequence::of($lower, ...$rest)->reverse();
        $stacked = Sequence::of(
            $this->get($stack->first())
        );

        $stacked = $stack->drop(1)->reduce(
            $stacked,
            function(Sequence $stacked, Name $decorator): Sequence {
                return $stacked->add(
                    $this->decorate($decorator, $stacked->last()->name())
                );
            }
        );
        $stacked = $stacked->add(
            $this->decorate($highest, $stacked->last()->name(), $name)
        );

        $self = clone $this;
        $self->definitions = $stacked->reduce(
            $self->definitions,
            static function(Map $definitions, Service $service): Map {
                return $definitions->put(
                    (string) $service->name(),
                    $service
                );
            }
        );

        return $self;
    }

    /**
     * This method must only be called from Dependencies::bind() through self::inject()
     *
     * The goal is to iteratively replace in Services the initial Dependency
     * instance by its resolved version, so other Dependency instances can rely
     * on it
     */
    public function feed(Name $dependency): self
    {
        $self = clone $this;
        $self->dependencies = $self->dependencies->feed($dependency, $self);

        return $self;
    }

    /**
     * @param MapInterface<string, mixed> $arguments
     */
    public function inject(MapInterface $arguments): self
    {
        $self = clone $this;
        $self->arguments = $self->arguments->bind($arguments);
        $self->building = $self->building->clear();
        $self->instances = $self->instances->clear();

        return $self->dependencies->bind($self);
    }

    public function build(Name $name): object
    {
        $definition = $this->get($name);
        $name = $definition->name();

        if ($this->instances->contains((string) $name)) {
            return $this->instances->get((string) $name);
        }

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

            $service = $definition->build($this);

            $this->instances = $this->instances->put((string) $name, $service);
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

    public function dependencies(): Dependencies
    {
        return $this->dependencies;
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

    /**
     * The list of exposed services name with their constructor
     *
     * @return MapInterface<Name, Constructor>
     */
    public function exposed(): MapInterface
    {
        return $this->exposed->reduce(
            new Map(Name::class, Constructor::class),
            static function(Map $exposed, string $name, Service $service): Map {
                return $exposed->put(
                    $service->exposedAs(),
                    $service->constructor()
                );
            }
        );
    }

    /**
     * @return SetInterface<Service>
     */
    public function all(): SetInterface
    {
        return Set::of(Service::class, ...$this->definitions->values());
    }

    private function decorate(
        Name $decorator,
        Name $decorated,
        Name $newName = null
    ): Service {
        if ($this->has($decorator)) {
            return $this->get($decorator)->decorate($decorated, $newName);
        }

        return $this->dependencies->decorate($decorator, $decorated, $newName);
    }
}
