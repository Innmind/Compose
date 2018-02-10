<?php
declare(strict_types = 1);

namespace Innmind\Compose\Compilation\Service\Constructor;

use Innmind\Compose\{
    Compilation\Service\Constructor,
    Compilation\Service\Argument
};
use Innmind\Immutable\Stream;

final class Merge implements Constructor
{
    private $arguments;

    public function __construct(
        Argument $argument1,
        Argument $argument2,
        Argument ...$arguments
    ) {
        $this->arguments = Stream::of(
            Argument::class,
            $argument1,
            $argument2,
            ...$arguments
        );
    }

    public function __toString(): string
    {
        return $this
            ->arguments
            ->drop(1)
            ->reduce(
                (string) $this->arguments->first(),
                static function(string $code, Argument $argument): string {
                    return sprintf("%s\n->merge(%s)", $code, $argument);
                }
            );
    }
}
