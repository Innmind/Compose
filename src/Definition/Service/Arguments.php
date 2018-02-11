<?php
declare(strict_types = 1);

namespace Innmind\Compose\Definition\Service;

use Innmind\Compose\Exception\ValueNotSupported;
use Innmind\Immutable\{
    StreamInterface,
    Stream
};

final class Arguments
{
    private static $defaults;
    private $arguments;

    public function __construct(string ...$arguments)
    {
        $arguments = Stream::of('string', ...$arguments);

        if ($arguments->size() === 0) {
            $arguments = self::defaults();
        }

        $this->arguments = $arguments;
    }

    /**
     * @param mixed $value
     */
    public function load($value): Argument
    {
        foreach ($this->arguments as $argument) {
            try {
                $build = $argument.'::fromValue';

                return $build($value, $this);
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
            Argument\Decorate::class,
            Argument\Reference::class,
            Argument\Unwind::class,
            Argument\Pair::class,
            Argument\Primitive::class
        );
    }
}
