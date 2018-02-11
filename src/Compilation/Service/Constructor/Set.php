<?php
declare(strict_types = 1);

namespace Innmind\Compose\Compilation\Service\Constructor;

use Innmind\Compose\{
    Compilation\Service\Constructor,
    Compilation\Service\Argument,
    Compilation\Service\Argument\Lazy
};
use Innmind\Immutable\Stream;

final class Set implements Constructor
{
    private $type;
    private $arguments;

    public function __construct(string $type, Argument ...$arguments)
    {
        $this->type = $type;
        $this->arguments = Stream::of(Argument::class, ...$arguments)->map(static function(Argument $argument): Argument {
            return new Lazy($argument);
        });
    }

    public function __toString(): string
    {
        $code = <<<PHP
\\Innmind\\Compose\\Lazy\\Set::of(
    '{$this->type}',
    {$this->arguments->join(",\n")}
)
PHP;

        return $code;
    }
}
