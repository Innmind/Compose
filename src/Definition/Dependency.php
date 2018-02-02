<?php
declare(strict_types = 1);

namespace Innmind\Compose\Definition;

use Innmind\Compose\{
    Definition\Dependency\Argument,
    Services,
    Lazy,
    Exception\ReferenceNotFound
};
use Innmind\Immutable\{
    Set,
    Map
};

final class Dependency
{
    private $name;
    private $services;
    private $arguments;

    public function __construct(
        Name $name,
        Services $services,
        Argument ...$arguments
    ) {
        $this->name = $name;
        $this->services = $services;
        $this->arguments = Set::of(Argument::class, ...$arguments);
    }

    public function name(): Name
    {
        return $this->name;
    }

    public function bind(Services $services): self
    {
        $arguments = $this
            ->arguments
            ->reduce(
                new Map('string', 'mixed'),
                static function(Map $arguments, Argument $argument) use ($services): Map {
                    return $arguments->put(
                        (string) $argument->name(),
                        $argument->resolve($services)
                    );
                }
            );

        $self = clone $this;
        $self->services = $self->services->inject($arguments);

        return $self;
    }

    public function lazy(Name $name): Lazy
    {
        if (!$this->has($name)) {
            throw new ReferenceNotFound((string) $name);
        }

        return new Lazy(
            $name,
            $this->services
        );
    }

    public function build(Name $name): object
    {
        return $this->lazy($name)->load();
    }

    public function has(Name $name): bool
    {
        if (!$this->services->has($name)) {
            return false;
        }

        $service = $this->services->get($name);

        if (!$service->exposed() || !$service->isExposedAs($name)) {
            return false;
        }

        return true;
    }

    public function dependsOn(self $other): bool
    {
        return $this->arguments->reduce(
            false,
            function(bool $dependsOn, Argument $argument) use ($other): bool {
                return $dependsOn || $argument->refersTo($other);
            }
        );
    }
}
