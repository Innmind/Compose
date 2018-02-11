<?php
declare(strict_types = 1);

namespace Innmind\Compose\Compilation;

use Innmind\Compose\Definition\{
    Dependency as Definition,
    Name
};
use Innmind\Immutable\{
    Sequence,
    Stream,
    Str
};

final class Dependencies
{
    private $dependencies;

    public function __construct(Definition ...$dependencies)
    {
        $this->dependencies = Stream::of(Definition::class, ...$dependencies)->reduce(
            Stream::of(Dependency::class),
            static function(Stream $dependencies, Definition $dependency): Stream {
                return $dependencies->add($dependency->compile());
            }
        );
    }

    public function properties(): string
    {
        return (string) $this
            ->dependencies
            ->reduce(
                new Sequence,
                static function(Sequence $properties, Dependency $dependency): Sequence {
                    return $properties->add((string) new PropertyName($dependency->name()));
                }
            )
            ->map(static function(string $property): string {
                return (string) Str::of($property)
                    ->prepend('    private $')
                    ->append(';');
            })
            ->join("\n");
    }

    public function exposed(): string
    {
        return (string) $this
            ->dependencies
            ->reduce(
                new Sequence,
                static function(Sequence $exposed, Dependency $dependency): Sequence {
                    return $exposed->add($dependency->exposed());
                }
            )
            ->join("\n\n");
    }

    public function __toString(): string
    {
        return (string) $this->dependencies->join("\n");
    }
}
