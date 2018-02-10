<?php
declare(strict_types = 1);

namespace Innmind\Compose\Compilation\Service\Argument;

use Innmind\Compose\{
    Compilation\Service\Argument,
    Compilation\MethodName,
    Definition\Name,
    Exception\LogicException
};

final class Tunnel implements Argument
{
    private $dependency;
    private $method;

    public function __construct(Name $dependency, MethodName $method)
    {
        $this->dependency = $dependency;
        $this->method = $method;
    }

    public function method(): MethodName
    {
        throw new LogicException('Tunnel cannot be accessed from outside the compiled container');
    }

    public function __toString(): string
    {
        return sprintf('$this->%s->%s()', $this->dependency, $this->method);
    }
}
