<?php
declare(strict_types = 1);

namespace Innmind\Compose\Compilation\Service\Argument;

use Innmind\Compose\{
    Compilation\Service\Argument,
    Compilation\MethodName,
    Definition\Name
};

final class Reference implements Argument
{
    private $method;

    public function __construct(Name $name)
    {
        $this->method = new MethodName($name);
    }

    public function method(): MethodName
    {
        return $this->method;
    }

    public function __toString(): string
    {
        return sprintf('$this->%s()', $this->method);
    }
}
