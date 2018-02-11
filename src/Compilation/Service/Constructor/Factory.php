<?php
declare(strict_types = 1);

namespace Innmind\Compose\Compilation\Service\Constructor;

use Innmind\Compose\{
    Compilation\Service\Constructor,
    Compilation\Service\Argument
};
use Innmind\Immutable\Stream;

final class Factory implements Constructor
{
    private $class;
    private $method;
    private $arguments;

    public function __construct(string $class, string $method, Argument ...$arguments)
    {
        $this->class = $class;
        $this->method = $method;
        $this->arguments = Stream::of(Argument::class, ...$arguments);
    }

    public function __toString(): string
    {
        $code = <<<PHP
\\{$this->class}::{$this->method}(
{$this->arguments->join(",\n")}
)
PHP;

        return $code;
    }
}
