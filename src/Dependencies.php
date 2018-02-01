<?php
declare(strict_types = 1);

namespace Innmind\Compose;

use Innmind\Compose\{
    Definition\Dependency,
    Definition\Name,
    Exception\ReferenceNotFound,
    Exception\NameNotNamespaced
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
        // todo: ensure no circular dependency when cross dependency argument supported
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

    public function build(Name $name): object
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
                ->build($name->withoutRoot());
        } catch (ReferenceNotFound $e) {
            throw new ReferenceNotFound((string) $name, 0, $e);
        }
    }
}
