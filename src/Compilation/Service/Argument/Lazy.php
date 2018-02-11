<?php
declare(strict_types = 1);

namespace Innmind\Compose\Compilation\Service\Argument;

use Innmind\Compose\Compilation\{
    Service\Argument,
    MethodName
};

final class Lazy implements Argument
{
    private $argument;

    public function __construct(Argument $argument)
    {
        $this->argument = $argument;
    }

    public function method(): MethodName
    {
        return $this->argument->method();
    }

    public function __toString(): string
    {
        if (!$this->argument instanceof Reference) {
            return (string) $this->argument;
        }

        return <<<PHP
new \\Innmind\\Compose\\Lazy(function() {
    return {$this->argument};
})
PHP;
    }
}
