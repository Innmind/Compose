<?php
declare(strict_types = 1);

namespace Innmind\Compose\Definition;

use Innmind\Compose\{
    Definition\Service\Constructor,
    Definition\Service\Argument,
    Definitions,
    Exception\ServiceCannotDecorateMultipleServices
};
use Innmind\Immutable\{
    StreamInterface,
    Stream
};

final class Service
{
    private $name;
    private $construct;
    private $arguments;
    private $exposeName;

    public function __construct(
        Name $name,
        Constructor $constructor,
        Argument ...$arguments
    ) {
        $this->name = $name;
        $this->construct = $constructor;
        $this->arguments = Stream::of(Argument::class, ...$arguments);

        $decorates = $this->arguments->filter(static function(Argument $argument): bool {
            return $argument instanceof Argument\Decorate;
        });

        if ($decorates->size() > 1) {
            throw new ServiceCannotDecorateMultipleServices((string) $name);
        }
    }

    public function exposeAs(Name $name): self
    {
        $self = clone $this;
        $self->exposeName = $name;

        return $self;
    }

    public function exposed(): bool
    {
        return $this->exposeName instanceof Name;
    }

    public function exposedAs(): Name
    {
        return $this->exposeName;
    }

    public function isExposedAs(Name $name): bool
    {
        return (string) $this->exposeName === (string) $name;
    }

    public function name(): Name
    {
        return $this->name;
    }

    public function constructor(): Constructor
    {
        return $this->construct;
    }

    /**
     * @return StreamInterface<Argument>
     */
    public function arguments(): StreamInterface
    {
        return $this->arguments;
    }

    public function build(Definitions $definitions): object
    {
        return ($this->construct)(...$this->arguments->reduce(
            Stream::of('mixed'),
            static function(Stream $arguments, Argument $argument) use ($definitions): Stream {
                return $argument->resolve($arguments, $definitions);
            }
        ));
    }
}
