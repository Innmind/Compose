<?php
declare(strict_types = 1);

namespace Innmind\Compose\Definition\Service\Constructor;

use Innmind\Compose\{
    Definition\Service\Constructor,
    Exception\ValueNotSupported,
    Lazy
};
use Innmind\Immutable\{
    Str,
    Stream as ImmutableStream,
    Sequence
};

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
        $arguments = Sequence::of(...$arguments)->map(static function($argument) {
            if ($argument instanceof Lazy) {
                return $argument->load();
            }

            return $argument;
        });

        return ImmutableStream::of($this->type, ...$arguments);
    }
}
