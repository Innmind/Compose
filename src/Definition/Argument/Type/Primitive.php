<?php
declare(strict_types = 1);

namespace Innmind\Compose\Definition\Argument\Type;

use Innmind\Compose\{
    Definition\Argument\Type,
    Exception\NotAPrimitiveType,
    Exception\ValueNotSupported
};
use Innmind\Immutable\Str;

final class Primitive implements Type
{
    private $function;

    public function __construct(string $primitive)
    {
        if (!function_exists('is_'.$primitive)) {
            throw new NotAPrimitiveType($primitive);
        }

        $this->function = 'is_'.$primitive;
    }

    /**
     * {@inheritdoc}
     */
    public function accepts($value): bool
    {
        return ($this->function)($value);
    }

    /**
     * {@inheritdoc}
     */
    public static function fromString(Str $value): Type
    {
        try {
            return new self((string) $value);
        } catch (NotAPrimitiveType $e) {
            throw new ValueNotSupported((string) $value, 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        return substr($this->function, 3);
    }
}
