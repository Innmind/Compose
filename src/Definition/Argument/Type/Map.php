<?php
declare(strict_types = 1);

namespace Innmind\Compose\Definition\Argument\Type;

use Innmind\Compose\{
    Definition\Argument\Type,
    Exception\ValueNotSupported
};
use Innmind\Immutable\{
    MapInterface,
    Str
};

final class Map implements Type
{
    private const PATTERN = '~^map<(?<key>.+), ?(?<value>.+)>$~';
    private $key;
    private $value;

    public function __construct(string $key, string $value)
    {
        $this->key = $key;
        $this->value = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function accepts($value): bool
    {
        if (!$value instanceof MapInterface) {
            return false;
        }

        return (string) $value->keyType() === $this->key &&
            (string) $value->valueType() === $this->value;
    }

    /**
     * {@inheritdoc}
     */
    public static function fromString(Str $value): Type
    {
        if (!$value->matches(self::PATTERN)) {
            throw new ValueNotSupported((string) $value);
        }

        $components = $value->capture(self::PATTERN);

        return new self(
            (string) $components->get('key'),
            (string) $components->get('value')
        );
    }
}
