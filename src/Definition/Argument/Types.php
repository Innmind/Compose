<?php
declare(strict_types = 1);

namespace Innmind\Compose\Definition\Argument;

use Innmind\Compose\Exception\ValueNotSupported;
use Innmind\Immutable\{
    StreamInterface,
    Stream,
    Str
};

final class Types
{
    private static $defaults;
    private $types;

    public function __construct(string ...$types)
    {
        $types = Stream::of('string', ...$types);

        if ($types->size() === 0) {
            $types = self::defaults();
        }

        $this->types = $types;
    }

    public function load(Str $value): Type
    {
        foreach ($this->types as $type) {
            try {
                $build = $type.'::fromString';

                return $build($value);
            } catch (ValueNotSupported $e) {
                //pass
            }
        }

        throw new ValueNotSupported((string) $value);
    }

    /**
     * @return StreamInterface<string>
     */
    public static function defaults(): StreamInterface
    {
        return self::$defaults ?? self::$defaults = Stream::of(
            'string',
            Type\Map::class,
            Type\Sequence::class,
            Type\Set::class,
            Type\Stream::class,
            Type\Primitive::class,
            Type\Instance::class
        );
    }
}
