<?php
declare(strict_types = 1);

namespace Innmind\Compose\Compilation\Service\Constructor;

use Innmind\Compose\{
    Compilation\Service\Constructor,
    Compilation\Service\Argument
};
use Innmind\Immutable\Stream;

final class Construct implements Constructor
{
    private $construct;
    private $arguments;

    public function __construct(string $construct, Argument ...$arguments)
    {
        $this->construct = $construct;
        $this->arguments = Stream::of(Argument::class, ...$arguments);
    }

    public function __toString(): string
    {
        return <<<PHP
new \\{$this->construct}(
{$this->arguments->join(",\n")}
)
PHP;
    }
}
