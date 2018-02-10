<?php
declare(strict_types = 1);

namespace Innmind\Compose\Compilation;

use Innmind\Compose\{
    Compilation\Service\Constructor,
    Definition\Service as Definition,
    Definition\Name
};

final class Service
{
    private $definition;
    private $constructor;
    private $property;
    private $method;

    public function __construct(Definition $service, Constructor $constructor)
    {
        $this->definition = $service;
        $this->constructor = $constructor;
        $this->property = new PropertyName($service->name());
        $this->method = new MethodName($service->name());
    }

    public function accessible(): bool
    {
        return $this->definition->exposed();
    }

    public function name(): Name
    {
        return $this->definition->exposedAs();
    }

    public function property(): PropertyName
    {
        return $this->property;
    }

    public function method(): MethodName
    {
        return $this->method;
    }

    public function __toString(): string
    {
        $code = <<<PHP
    public function {$this->method}(): object
    {
        return \$this->{$this->property} ?? \$this->{$this->property} = {$this->constructor};
    }
PHP;

        return $code;
    }
}
