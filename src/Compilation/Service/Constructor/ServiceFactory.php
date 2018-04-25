<?php
declare(strict_types = 1);

namespace Innmind\Compose\Compilation\Service\Constructor;

use Innmind\Compose\{
    Compilation\Service\Constructor,
    Compilation\Service\Argument
};
use Innmind\Immutable\Stream;

final class ServiceFactory implements Constructor
{
    private $factory;
    private $method;
    private $arguments;

    public function __construct(string $method, Argument $factory, Argument ...$arguments)
    {
        $this->factory = $factory;
        $this->method = $method;
        $this->arguments = Stream::of(Argument::class, ...$arguments);
    }

    public function __toString(): string
    {
        return <<<PHP
{$this->factory}->{$this->method}(
{$this->arguments->join(",\n")}
)
PHP;
    }
}
