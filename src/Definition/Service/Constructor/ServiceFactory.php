<?php
declare(strict_types = 1);

namespace Innmind\Compose\Definition\Service\Constructor;

use Innmind\Compose\{
    Definition\Service\Constructor,
    Exception\ValueNotSupported,
    Lazy,
    Compilation\Service\Constructor as CompiledConstructor,
    Compilation\Service\Constructor\ServiceFactory as CompiledServiceFactory,
    Compilation\Service\Argument as CompiledArgument,
};
use Innmind\Immutable\{
    Str,
    Sequence,
};

final class ServiceFactory implements Constructor
{
    private $method;

    private function __construct(string $method)
    {
        $this->method = $method;
    }

    /**
     * {@inheritdoc}
     */
    public static function fromString(Str $value): Constructor
    {
        if (!$value->matches('~^\$factory->\S+$~')) {
            throw new ValueNotSupported((string) $value);
        }

        [, $method] = $value->split('->');

        return new self((string) $method);
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(...$arguments): object
    {
        $arguments = Sequence::of(...$arguments)->map(static function($argument) {
            if ($argument instanceof Lazy) {
                return $argument->load();
            }

            return $argument;
        });

        return $arguments->first()->{$this->method}(...$arguments->drop(1));
    }

    public function compile(CompiledArgument ...$arguments): CompiledConstructor
    {
        return new CompiledServiceFactory($this->method, ...$arguments);
    }

    public function __toString(): string
    {
        return sprintf('$factory->%s', $this->method);
    }
}
