<?php
declare(strict_types = 1);

namespace Innmind\Compose\Compilation;

use Innmind\Compose\{
    Definition\Name,
    Definition\Dependency\Parameter,
    Services as Container,
};
use Innmind\Immutable\{
    Stream,
    Sequence
};

final class Dependency
{
    private $name;
    private $services;
    private $parameters;

    public function __construct(
        Name $name,
        Container $services,
        Parameter ...$parameters
    ) {
        $this->name = $name;
        $this->services = $services;
        $this->parameters = Stream::of(Parameter::class, ...$parameters);
    }

    public function name(): Name
    {
        return $this->name;
    }

    public function exposed(): string
    {
        return (string) $this
            ->services
            ->exposed()
            ->keys()
            ->reduce(
                new Sequence,
                function(Sequence $methods, Name $service): Sequence {
                    $method = new MethodName($this->name->add($service));

                    return $methods->add(<<<PHP
    private function $method()
    {
        return \$this->{$this->name}->get('$service');
    }
PHP
                    );
                }
            )
            ->join("\n\n");
    }

    public function __toString(): string
    {
        $container = $this->services->compile();
        $property = new PropertyName($this->name);

        return <<<PHP
        \$arguments = (new \\Innmind\\Immutable\\Map('string', 'mixed')){$this->parameters()};
        \$this->$property = $container
PHP;
    }

    private function parameters(): string
    {
        if ($this->parameters->size() === 0) {
            return '';
        }

        return (string) $this
            ->parameters
            ->reduce(
                new Sequence,
                static function(Sequence $parameters, Parameter $parameter): Sequence {
                    return $parameters->add($parameter->compile());
                }
            )
            ->join("\n")
            ->prepend("\n");
    }
}
