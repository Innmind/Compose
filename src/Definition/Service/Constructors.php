<?php
declare(strict_types = 1);

namespace Innmind\Compose\Definition\Service;

use Innmind\Compose\Exception\ValueNotSupported;
use Innmind\Immutable\{
    StreamInterface,
    Stream,
    Str
};

final class Constructors
{
    private static $defaults;
    private $constructors;

    public function __construct(string ...$constructors)
    {
        $constructors = Stream::of('string', ...$constructors);

        if ($constructors->size() === 0) {
            $constructors = self::defaults();
        }

        $this->constructors = $constructors;
    }

    public function load(Str $value): Constructor
    {
        foreach ($this->constructors as $constructor) {
            try {
                $build = $constructor.'::fromString';

                return $build($value);
            } catch (ValueNotSupported $e) {
                //pass
            }
        }

        throw new ValueNotSupported(var_export($value, true));
    }

    /**
     * @return StreamInterface<string>
     */
    public static function defaults(): StreamInterface
    {
        return self::$defaults ?? self::$defaults = Stream::of(
            'string',
            Constructor\Factory::class,
            Constructor\ServiceFactory::class,
            Constructor\Set::class,
            Constructor\Stream::class,
            Constructor\Map::class,
            Constructor\Merge::class,
            Constructor\Construct::class
        );
    }
}
