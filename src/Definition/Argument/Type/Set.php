<?php
declare(strict_types = 1);

namespace Innmind\Compose\Definition\Argument\Type;

use Innmind\Compose\{
    Definition\Argument\Type,
    Exception\ValueNotSupported
};
use Innmind\Immutable\{
    SetInterface,
    Str
};

final class Set implements Type
{
    private const PATTERN = '~^set<(?<type>.+)>$~';
    private $type;

    public function __construct(string $type)
    {
        $this->type = $type;
    }

    /**
     * {@inheritdoc}
     */
    public function accepts($value): bool
    {
        if (!$value instanceof SetInterface) {
            return false;
        }

        return (string) $value->type() === $this->type;
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

        return new self((string) $components->get('type'));
    }
}
