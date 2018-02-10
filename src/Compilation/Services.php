<?php
declare(strict_types = 1);

namespace Innmind\Compose\Compilation;

use Innmind\Compose\{
    Services as Container,
    Definition
};
use Innmind\Immutable\{
    SetInterface,
    Set,
    Str
};

final class Services
{
    private $services;
    private $arguments;
    private $dependencies;

    public function __construct(Container $services)
    {
        $this->services = $services
            ->all()
            ->filter(static function(Definition\Service $service): bool {
                return !$service->decorates();
            })
            ->reduce(
                Set::of(Service::class),
                static function(Set $services, Definition\Service $service): Set {
                    return $services->add($service->compile());
                }
            );
        $this->arguments = $services->arguments()->compile();
        $this->dependencies = $services->dependencies()->compile();
    }

    public function __toString(): string
    {
        $methods = $this
            ->services
            ->join("\n\n")
            ->append("\n\n")
            ->append((string) $this->arguments)
            ->append("\n\n")
            ->append($this->dependencies->exposed());
        $switchGet = $this->generateSwitchGet();
        $switchHas = $this->generateSwitchHas();

        return <<<PHP
new class(\$arguments) implements ContainerInterface {
    private \$arguments;

    // Dependencies
{$this->dependencies->properties()}

    // Services instances
{$this->properties()}

    public function __construct(MapInterface \$arguments)
    {
        \$this->arguments = \$arguments;
{$this->dependencies}
    }

    public function get(\$id): object
    {
        switch (\$id) {
$switchGet
        }

        throw new NotFound(\$id);
    }

    public function has(\$id): bool
    {
        switch (\$id) {
$switchHas
                return true;
        }

        return false;
    }

    $methods
};
PHP;
    }

    private function accessible(): SetInterface
    {
        return $this->services->filter(static function(Service $service): bool {
            return $service->accessible();
        });
    }

    private function properties(): Str
    {
        return $this
            ->services
            ->reduce(
                Set::of(Str::class),
                static function(Set $properties, Service $service): Set {
                    return $properties->add(
                        Str::of((string) $service->property())
                    );
                }
            )
            ->map(static function(Str $property): Str {
                return $property
                    ->prepend('    private $')
                    ->append(';');
            })
            ->join("\n");
    }

    private function generateSwitchGet(): Str
    {
        return $this
            ->accessible()
            ->reduce(
                Set::of('string'),
                static function(Set $accessible, Service $service): Set {
                    $code = <<<PHP
            case '{$service->name()}':
                return \$this->{$service->method()}();
PHP;

                    return $accessible->add($code);
                }
            )
            ->join("\n");
    }

    private function generateSwitchHas(): Str
    {
        return $this
            ->accessible()
            ->reduce(
                Set::of('string'),
                static function(Set $accessible, Service $service): Set {
                    $code = <<<PHP
            case '{$service->name()}':
PHP;

                    return $accessible->add($code);
                }
            )
            ->join("\n");
    }
}
