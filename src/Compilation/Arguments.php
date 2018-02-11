<?php
declare(strict_types = 1);

namespace Innmind\Compose\Compilation;

use Innmind\Compose\{
    Arguments as Container,
    Definition
};
use Innmind\Immutable\Set;

final class Arguments
{
    private $arguments;

    public function __construct(Container $arguments)
    {
        $this->arguments = $arguments
            ->all()
            ->reduce(
                Set::of(Argument::class),
                static function(Set $arguments, Definition\Argument $argument): Set {
                    return $arguments->add($argument->compile());
                }
            );
    }

    public function __toString(): string
    {
        return (string) $this->arguments->join("\n\n");
    }
}
