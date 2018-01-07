<?php
declare(strict_types = 1);

namespace Innmind\Compose;

use Innmind\Compose\{
    Definition\Argument,
    Definition\Argument\Name,
    Exception\MissingArgument,
    Exception\ArgumentNotProvided
};
use Innmind\Immutable\{
    Sequence,
    MapInterface,
    Map
};

final class Arguments
{
    private $arguments;
    private $values;

    public function __construct(Argument ...$arguments)
    {
        $this->arguments = Sequence::of(...$arguments)->reduce(
            new Map('string', Argument::class),
            static function(Map $arguments, Argument $argument): Map {
                return $arguments->put(
                    (string) $argument->name(),
                    $argument
                );
            }
        );
        $this->values = new Map('string', 'mixed');
    }

    /**
     * @param MapInterface<string, mixed> $values
     *
     * @throws MissingArgument
     */
    public function bind(MapInterface $values): self
    {
        if (
            (string) $values->keyType() !== 'string' ||
            (string) $values->valueType() !== 'mixed'
        ) {
            throw new \TypeError('Argument 1 must be of type MapInterface<string, mixed>');
        }

        $this
            ->arguments
            ->filter(static function(string $name, Argument $argument): bool {
                return !$argument->optional() && !$argument->hasDefault();
            })
            ->foreach(static function(string $name) use ($values): void {
                if (!$values->contains($name)) {
                    throw new MissingArgument($name);
                }
            });
        $this
            ->arguments
            ->filter(static function(string $name) use ($values): bool {
                return $values->contains($name);
            })
            ->foreach(static function(string $name, Argument $argument) use ($values): void {
                $argument->validate($values->get($name));
            });

        $self = clone $this;
        $self->values = $values;

        return $self;
    }

    /**
     * @throws ArgumentNotProvided
     *
     * @return mixed
     */
    public function get(Name $name)
    {
        if (!$this->values->contains((string) $name)) {
            throw new ArgumentNotProvided($this->arguments->get((string) $name));
        }

        return $this->values->get((string) $name);
    }
}
