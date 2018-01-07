<?php
declare(strict_types = 1);

namespace Innmind\Compose\Definition\Argument\Type;

use Innmind\Compose\{
    Definition\Argument\Type,
    Exception\NotAPrimitiveType
};

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
}
