<?php
declare(strict_types = 1);

namespace Innmind\Compose\Definition;

use Innmind\Compose\{
    Definition\Service\Constructor,
    Definition\Service\Argument,
    Exception\ServiceCannotDecorateMultipleServices
};
use Innmind\Immutable\{
    StreamInterface,
    Stream
};

final class Service
{
    private $name;
    private $constructor;
    private $arguments;
    private $exposeName;

    public function __construct(
        Name $name,
        Constructor $constructor,
        Argument ...$arguments
    ) {
        $this->name = $name;
        $this->constructor = $constructor;
        $this->arguments = Stream::of(Argument::class, ...$arguments);

        $decorates = $this->arguments->filter(static function(Argument $argument): bool {
            return $argument->decorates();
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

    public function name(): Name
    {
        return $this->name;
    }

    public function constructor(): Constructor
    {
        return $this->constructor;
    }

    /**
     * @return StreamInterface<Argument>
     */
    public function arguments(): StreamInterface
    {
        return $this->arguments;
    }
}
