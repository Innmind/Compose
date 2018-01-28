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
    StreamInterface,
    Str
};

final class Reference implements Argument
{
    private $name;

    public function __construct(Name $name)
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

        if ((string) $value->substring(0, 1) !== '$') {
            throw new ValueNotSupported((string) $value);
        }

        return new self(new Name(
            (string) $value->substring(1)
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
            return $built->add(
                $definitions->arguments()->get($this->name)
            );
        } catch (ArgumentNotProvided $e) {
            if ($e->argument()->hasDefault()) {
                return $built->add(
                    $definitions->build($e->argument()->default())
                );
            }

            if ($e->argument()->optional()) {
                return $built->add(null);
            }

            throw $e;
        } catch (ArgumentNotDefined $e) {
            //pass
        }

        return $built->add($definitions->build($this->name));
    }
}
