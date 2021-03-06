<?php
declare(strict_types = 1);

namespace Innmind\Compose\Definition\Service\Argument;

use Innmind\Compose\{
    Definition\Service\Argument,
    Definition\Service\Arguments,
    Definition\Name,
    Services,
    Lazy,
    Compilation\Service\Argument as CompiledArgument,
    Compilation\Service\Argument\Reference as CompiledReference,
    Exception\ValueNotSupported,
    Exception\ArgumentNotProvided,
    Exception\ArgumentNotDefined,
    Exception\ReferenceNotFound
};
use Innmind\Immutable\{
    StreamInterface,
    Str
};

final class Reference implements Argument, HoldReference
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
        Services $services
    ): StreamInterface {
        try {
            return $built->add(
                $services->arguments()->get($this->name)
            );
        } catch (ArgumentNotProvided $e) {
            if ($e->argument()->hasDefault()) {
                return $built->add(Lazy::service(
                    $e->argument()->default(),
                    $services
                ));
            }

            if ($e->argument()->optional()) {
                return $built->add(null);
            }

            throw $e;
        } catch (ArgumentNotDefined $e) {
            //pass
        }

        try {
            return $built->add(
                $services->dependencies()->lazy($this->name)
            );
        } catch (ReferenceNotFound $e) {
            //pass
        }

        return $built->add(Lazy::service($this->name, $services));
    }

    public function reference(): Name
    {
        return $this->name;
    }

    public function compile(): CompiledArgument
    {
        return new CompiledReference($this->name);
    }
}
