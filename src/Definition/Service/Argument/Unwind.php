<?php
declare(strict_types = 1);

namespace Innmind\Compose\Definition\Service\Argument;

use Innmind\Compose\{
    Definition\Service\Argument,
    Definition\Service\Arguments,
    Definition\Name,
    Services,
    Compilation\Service\Argument as CompiledArgument,
    Compilation\Service\Argument\Unwind as CompiledUnwind,
    Exception\ValueNotSupported,
    Exception\ArgumentNotProvided,
    Exception\ArgumentNotDefined,
    Exception\ReferenceNotFound
};
use Innmind\Immutable\{
    Str,
    StreamInterface,
    Stream
};

final class Unwind implements Argument, HoldReference
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
        Services $services
    ): StreamInterface {
        try {
            return $built->append(Stream::of(
                'mixed',
                ...$services->arguments()->get($this->name)
            ));
        } catch (ArgumentNotProvided $e) {
            if ($e->argument()->hasDefault()) {
                return $built->append(Stream::of(
                    'mixed',
                    ...$services->build($e->argument()->default())
                ));
            }

            if ($e->argument()->optional()) {
                return $built;
            }

            throw $e;
        } catch (ArgumentNotDefined $e) {
            //pass
        }

        try {
            return $built->append(Stream::of(
                'mixed',
                ...$services->dependencies()->build($this->name)
            ));
        } catch (ReferenceNotFound $e) {
            //pass
        }

        return $built->append(Stream::of(
            'mixed',
            ...$services->build($this->name)
        ));
    }

    public function reference(): Name
    {
        return $this->name;
    }

    public function compile(): CompiledArgument
    {
        return new CompiledUnwind($this->name);
    }
}
