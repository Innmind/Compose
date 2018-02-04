<?php
declare(strict_types = 1);

namespace Innmind\Compose\Definition\Service\Constructor;

use Innmind\Compose\{
    Definition\Service\Constructor,
    Exception\ValueNotSupported,
    Lazy\Set as LazySet
};
use Innmind\Immutable\Str;

final class Set implements Constructor
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
        if (!$value->matches('~^set<\S+>$~')) {
            throw new ValueNotSupported((string) $value);
        }

        $components = $value->capture('~^set<(?<type>\S+)>$~');

        return new self((string) $components->get('type'));
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(...$arguments): object
    {
        return LazySet::of($this->type, ...$arguments);
    }

    public function __toString(): string
    {
        return sprintf('set<%s>', $this->type);
    }
}
