<?php
declare(strict_types = 1);

namespace Innmind\Compose\Definition\Argument\Type;

use Innmind\Compose\{
    Definition\Argument\Type,
    Exception\ValueNotSupported
};
use Innmind\Immutable\{
    StreamInterface,
    Str
};

final class Stream implements Type
{
    private const PATTERN = '~^stream<(?<type>.+)>$~';
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
        if (!$value instanceof StreamInterface) {
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

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        return sprintf('stream<%s>', $this->type);
    }
}
