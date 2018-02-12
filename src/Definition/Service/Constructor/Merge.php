<?php
declare(strict_types = 1);

namespace Innmind\Compose\Definition\Service\Constructor;

use Innmind\Compose\{
    Definition\Service\Constructor,
    Exception\ValueNotSupported,
    Compilation\Service\Constructor as CompiledConstructor,
    Compilation\Service\Constructor\Merge as CompiledMerge,
    Compilation\Service\Argument as CompiledArgument,
    Lazy,
};
use Innmind\Immutable\{
    Str,
    SetInterface,
    MapInterface,
    Stream,
    Sequence,
    Exception\InvalidArgumentException
};

final class Merge implements Constructor
{
    /**
     * {@inheritdoc}
     */
    public static function fromString(Str $value): Constructor
    {
        if ((string) $value !== 'merge') {
            throw new ValueNotSupported((string) $value);
        }

        return new self;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(...$arguments): object
    {
        $arguments = Sequence::of(...$arguments)->map(static function($argument) {
            if ($argument instanceof Lazy) {
                return $argument->load();
            }

            return $argument;
        });

        try {
            $arguments = Stream::of(SetInterface::class, ...$arguments);
        } catch (InvalidArgumentException $e) {
            $arguments = Stream::of(MapInterface::class, ...$arguments);
        }

        return $arguments
            ->drop(1)
            ->reduce(
                $arguments->first(),
                static function($structure, $element) {
                    return $structure->merge($element);
                }
            );
    }

    public function compile(CompiledArgument ...$arguments): CompiledConstructor
    {
        return new CompiledMerge(...$arguments);
    }

    public function __toString(): string
    {
        return 'merge';
    }
}
