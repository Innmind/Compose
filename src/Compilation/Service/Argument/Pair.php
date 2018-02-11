<?php
declare(strict_types = 1);

namespace Innmind\Compose\Compilation\Service\Argument;

use Innmind\Compose\{
    Compilation\Service\Argument,
    Compilation\MethodName,
    Exception\LogicException
};

final class Pair implements Argument
{
    private $key;
    private $value;

    public function __construct(Argument $key, Argument $value)
    {
        $this->key = new Lazy($key);
        $this->value = new Lazy($value);
    }

    public function method(): MethodName
    {
        throw new LogicException('Argument pair cannot be accessed from outside the compiled container');
    }

    public function __toString(): string
    {
        return <<<PHP
new \\Innmind\\Immutable\\Pair(
    {$this->key},
    {$this->value}
)
PHP;
    }
}
