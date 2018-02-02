<?php
declare(strict_types = 1);

namespace Innmind\Compose;

use Innmind\Compose\{
    Definition\Dependency,
    Definition\Name,
    Exception\ReferenceNotFound,
    Exception\NameNotNamespaced,
    Exception\CircularDependency
};
use Innmind\Immutable\{
    Sequence,
    Map
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

    public function bind(Services $services): self
    {
        $self = clone $this;
        $self->dependencies = $self
            ->dependencies
            ->map(static function(string $name, Dependency $dependency) use ($services): Dependency {
                return $dependency->bind($services);
            });

        return $self;
    }

    public function lazy(Name $name): Lazy
    {
        try {
            $root = $name->root();
        } catch (NameNotNamespaced $e) {
            throw new ReferenceNotFound((string) $name);
        }

        if (!$this->dependencies->contains((string) $root)) {
            throw new ReferenceNotFound((string) $name);
        }

        try {
            return $this
                ->dependencies
                ->get((string) $root)
                ->lazy($name->withoutRoot());
        } catch (ReferenceNotFound $e) {
            throw new ReferenceNotFound((string) $name, 0, $e);
        }
    }

    public function build(Name $name): object
    {
        return $this->lazy($name)->load();
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
