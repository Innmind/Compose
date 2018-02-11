<?php
declare(strict_types = 1);

namespace Innmind\Compose\Compilation\Service\Argument;

use Innmind\Compose\{
    Compilation\Service\Argument,
    Compilation\MethodName,
    Exception\LogicException
};

final class Primitive implements Argument
{
    private $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public function method(): MethodName
    {
        throw new LogicException('Primitive cannot be accessed from outside the compiled container');
    }

    public function __toString(): string
    {
        return var_export($this->value, true);
    }
}
