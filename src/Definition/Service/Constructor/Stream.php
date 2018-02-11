<?php
declare(strict_types = 1);

namespace Innmind\Compose\Definition\Service\Constructor;

use Innmind\Compose\{
    Definition\Service\Constructor,
    Exception\ValueNotSupported,
    Lazy\Stream as LazyStream,
    Compilation\Service\Constructor as CompiledConstructor,
    Compilation\Service\Constructor\Stream as CompiledStream,
    Compilation\Service\Argument as CompiledArgument
};
use Innmind\Immutable\Str;

final class Stream implements Constructor
{
    private $type;

    private function __construct(string $type)
    {
        $this->type = $type;
    }

    /**
     * {@inheritdoc}
     */
    public static function fromString(Str $value): Constructor
    {
        if (!$value->matches('~^stream<\S+>$~')) {
            throw new ValueNotSupported((string) $value);
        }

        $components = $value->capture('~^stream<(?<type>\S+)>$~');

        return new self((string) $components->get('type'));
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(...$arguments): object
    {
        return new LazyStream($this->type, ...$arguments);
    }

    public function compile(CompiledArgument ...$arguments): CompiledConstructor
    {
        return new CompiledStream($this->type, ...$arguments);
    }

    public function __toString(): string
    {
        return sprintf('stream<%s>', $this->type);
    }
}
