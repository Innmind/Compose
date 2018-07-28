<?php
declare(strict_types = 1);

namespace Innmind\Compose;

use Innmind\Compose\{
    Definition\Dependency,
    Definition\Name,
    Definition\Service,
    Definition\Service\Argument,
    Exception\ReferenceNotFound,
    Exception\NameNotNamespaced,
    Exception\CircularDependency,
    Exception\LogicException,
    Compilation\Dependencies as CompiledDependencies
};
use Innmind\Immutable\{
    Sequence,
    MapInterface,
    Map,
    StreamInterface
};

final class Dependencies
{
    private $dependencies;

    public function __construct(Dependency ...$dependencies)
    {
        $this->dependencies = Sequence::of(...$dependencies)->reduce(
            new Map('string', Dependency::class),
            static function(Map $dependencies, Dependency $dependency): Map {
                return $dependencies->put(
                    (string) $dependency->name(),
                    $dependency
                );
            }
        );
        $this->assertNoCircularDependency();
    }

    public function feed(Name $name, Services $services): self
    {
        $self = clone $this;
        $self->dependencies = $self->dependencies->put(
            (string) $name,
            $self->dependencies->get((string) $name)->bind($services)
        );

        return $self;
    }

    public function bind(Services $services): Services
    {
        return $this
            ->dependencies
            ->values()
            ->sort(static function(Dependency $a, Dependency $b) use ($services): bool {
                if ($a->need($services)) {
                    return true;
                }

                return $a->dependsOn($b);
            })
            ->reduce(
                $services,
                static function(Services $services, Dependency $dependency): Services {
                    return $services->feed($dependency->name());
                }
            );
    }

    public function lazy(Name $name): Lazy
    {
        try {
            return $this
                ->get($name)
                ->lazy($name->withoutRoot());
        } catch (ReferenceNotFound $e) {
            throw new ReferenceNotFound((string) $name, 0, $e);
        }
    }

    public function build(Name $name): object
    {
        return $this->lazy($name)->load();
    }

    public function decorate(
        Name $decorator,
        Name $decorated,
        Name $newName = null
    ): Service {
        try {
            return $this
                ->get($decorator)
                ->decorate($decorator->withoutRoot(), $decorated, $newName);
        } catch (ReferenceNotFound $e) {
            throw new ReferenceNotFound((string) $decorator, 0, $e);
        }
    }

    /**
     * @param StreamInterface<mixed> $arguments
     *
     * @return StreamInterface<mixed>
     */
    public function extract(
        Name $name,
        StreamInterface $arguments,
        Argument $argument
    ): StreamInterface {
        try {
            return $this
                ->get($name)
                ->extract($name->withoutRoot(), $arguments, $argument);
        } catch (ReferenceNotFound $e) {
            throw new ReferenceNotFound((string) $name, 0, $e);
        }
    }

    private function get(Name $name): Dependency
    {
        try {
            $root = $name->root();
        } catch (NameNotNamespaced $e) {
            throw new ReferenceNotFound((string) $name);
        }

        if (!$this->dependencies->contains((string) $root)) {
            throw new ReferenceNotFound((string) $name);
        }

        return $this->dependencies->get((string) $root);
    }

    /**
     * Return the list of exposed services per dependency
     *
     * @return MapInterface<Name, MapInterface<Name, Constructor>>
     */
    public function exposed(): MapInterface
    {
        return $this->dependencies->reduce(
            new Map(Name::class, MapInterface::class),
            static function(Map $exposed, string $name, Dependency $dependency): Map {
                return $exposed->put(
                    $dependency->name(),
                    $dependency->exposed()
                );
            }
        );
    }

    public function compile(): CompiledDependencies
    {
        return new CompiledDependencies(
            ...$this->dependencies->values()
        );
    }

    private function assertNoCircularDependency(): void
    {
        $this->dependencies->foreach(function(string $name, Dependency $dependency): void {
            $this
                ->dependencies
                ->remove($name)
                ->foreach(static function(string $name, Dependency $other) use ($dependency): void {
                    if (!$dependency->dependsOn($other)) {
                        return;
                    }

                    if (!$other->dependsOn($dependency)) {
                        return;
                    }

                    throw new CircularDependency(sprintf(
                        '%s -> %s -> %s',
                        $dependency->name(),
                        $other->name(),
                        $dependency->name()
                    ));
                });
        });
    }
}
