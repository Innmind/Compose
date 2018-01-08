<?php
declare(strict_types = 1);

namespace Innmind\Compose;

use Innmind\Compose\{
    Definition\Name,
    Definition\Service,
    Definition\Service\Argument,
    Exception\ArgumentNotProvided
};
use Innmind\Immutable\{
    Sequence,
    MapInterface,
    Map,
    Pair
};

final class Definitions
{
    private $definitions;
    private $exposed;
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

    public function build(Name $name): object
    {
        $definition = $this->get($name);
        $arguments = $this->buildArguments($definition);
        $construct = $definition->constructor();

        return $construct(...$arguments);
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

    public function buildArguments(Service $service): Sequence
    {
        return $service
            ->arguments()
            ->reduce(
                new Sequence,
                function(Sequence $arguments, Argument $argument): Sequence {
                    // @todo: handle the decoration
                    $value = $this->fetchArgumentValue($argument);

                    if ($argument->toUnwind()) {
                        return $arguments->append(new Sequence(...$value ?? []));
                    }

                    return $arguments->add($value);
                }
            );
    }

    public function fetchArgumentValue(Argument $argument)
    {
        try {
            return $this->arguments->get($argument->reference());
        } catch (ArgumentNotProvided $e) {
            //pass
        }

        if ($e->argument()->hasDefault()) {
            return $this->build($e->argument()->default());
        }

        //null as the argument must be optional here, requirement as been
        //checked earlier

        return null;
    }
}
