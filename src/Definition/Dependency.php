<?php
declare(strict_types = 1);

namespace Innmind\Compose\Definition;

use Innmind\Compose\{
    Definition\Dependency\Parameter,
    Definition\Service\Argument,
    Services,
    Lazy,
    Exception\ReferenceNotFound,
    Exception\ArgumentNotExtractable
};
use Innmind\Immutable\{
    Set,
    Map,
    StreamInterface
};

final class Dependency
{
    private $name;
    private $services;
    private $parameters;

    public function __construct(
        Name $name,
        Services $services,
        Parameter ...$parameters
    ) {
        $this->name = $name;
        $this->services = $services;
        $this->parameters = Set::of(Parameter::class, ...$parameters);
    }

    public function name(): Name
    {
        return $this->name;
    }

    public function bind(Services $services): self
    {
        $parameters = $this
            ->parameters
            ->reduce(
                new Map('string', 'mixed'),
                static function(Map $parameters, Parameter $parameter) use ($services): Map {
                    return $parameters->put(
                        (string) $parameter->name(),
                        $parameter->resolve($services)
                    );
                }
            );

        $self = clone $this;
        $self->services = $self->services->inject($parameters);

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
        return $this->parameters->reduce(
            false,
            function(bool $dependsOn, Parameter $parameter) use ($other): bool {
                return $dependsOn || $parameter->refersTo($other);
            }
        );
    }

    public function decorate(
        Name $decorator,
        Name $decorated,
        Name $newName = null
    ): Service {
        if (!$this->has($decorator)) {
            throw new ReferenceNotFound((string) $decorator);
        }

        $service = $this->services->get($decorator);

        return $service->tunnel($this->name, $decorated, $newName);
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
        if (!$this->has($name)) {
            throw new ReferenceNotFound((string) $name);
        }

        $service = $this->services->get($name);

        if (
            !$service->decorates() ||
            !$service->arguments()->contains($argument)
        ) {
            throw new ArgumentNotExtractable;
        }

        return $argument->resolve(
            $arguments,
            $this->services
        );
    }
}
