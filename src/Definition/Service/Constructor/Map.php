<?php
declare(strict_types = 1);

namespace Innmind\Compose\Definition\Service\Constructor;

use Innmind\Compose\{
    Definition\Service\Constructor,
    Exception\ValueNotSupported,
    Lazy\Map as LazyMap
};
use Innmind\Immutable\Str;

final class Map implements Constructor
{
    private $key;
    private $value;

    private function __construct(string $key, string $value)
    {
        $this->key = $key;
        $this->value = $value;
    }

    /**
     * {@inheritdoc}
     */
    public static function fromString(Str $value): Constructor
    {
        if (!$value->matches('~^map<\S+, ?\S+>$~')) {
            throw new ValueNotSupported((string) $value);
        }

        $components = $value->capture('~^map<(?<key>\S+), ?(?<value>\S+)>$~');

        return new self(
            (string) $components->get('key'),
            (string) $components->get('value')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(...$arguments): object
    {
        return LazyMap::of($this->key, $this->value, ...$arguments);
    }

    public function __toString(): string
    {
        return sprintf('map<%s, %s>', $this->key, $this->value);
    }
}
