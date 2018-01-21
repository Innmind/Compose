<?php
declare(strict_types = 1);

namespace Innmind\Compose\Definition\Service\Constructor;

use Innmind\Compose\{
    Definition\Service\Constructor,
    Exception\ValueNotSupported
};
use Innmind\Immutable\{
    Str,
    Stream as ImmutableStream
};

final class Stream implements Constructor
{
    private $type;
    private $method;

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
        return ImmutableStream::of($this->type, ...$arguments);
    }
}
