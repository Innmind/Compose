<?php
declare(strict_types = 1);

namespace Innmind\Compose\Definition\Service\Argument;

use Innmind\Compose\{
    Definition\Service\Argument,
    Definition\Service\Arguments,
    Definition\Name,
    Definitions,
    Exception\ValueNotSupported,
    Exception\ArgumentNotProvided,
    Exception\ArgumentNotDefined
};
use Innmind\Immutable\{
    Str,
    StreamInterface,
    Stream
};

final class Unwind implements Argument
{
    private $name;

    private function __construct(Name $name)
    {
        $this->name = $name;
    }

    /**
     * {@inheritdoc}
     */
    public static function fromValue($value, Arguments $arguments): Argument
    {
        if (!is_string($value)) {
            throw new ValueNotSupported;
        }

        $value = Str::of($value);

        if ((string) $value->substring(0, 4) !== '...$') {
            throw new ValueNotSupported((string) $value);
        }

        return new self(new Name(
            (string) $value->substring(4)
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(
        StreamInterface $built,
        Definitions $definitions
    ): StreamInterface {
        try {
            $value = $definitions->arguments()->get($this->name);
        } catch (ArgumentNotProvided $e) {
            if ($e->argument()->hasDefault()) {
                $value = $definitions->build($e->argument()->default());
            }

            if ($e->argument()->optional()) {
                $value = [];
            }
        } catch (ArgumentNotDefined $e) {
            $value = $definitions->build($this->name);
        }

        return $built->append(Stream::of(
            'mixed',
            ...$value
        ));
    }
}
