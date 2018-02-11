<?php
declare(strict_types = 1);

namespace Innmind\Compose\Definition;

use Innmind\Compose\{
    Definition\Argument\Type,
    Exception\InvalidArgument,
    Compilation\Argument as CompiledArgument
};

final class Argument
{
    private $name;
    private $type;
    private $optional = false;
    private $default;

    public function __construct(Name $name, Type $type)
    {
        $this->name = $name;
        $this->type = $type;
    }

    public function name(): Name
    {
        return $this->name;
    }

    public function type(): Type
    {
        return $this->type;
    }

    public function makeOptional(): self
    {
        $self = clone $this;
        $self->optional = true;

        return $self;
    }

    public function defaultsTo(Name $name): self
    {
        $self = clone $this;
        $self->default = $name;

        return $self;
    }

    public function optional(): bool
    {
        return $this->optional;
    }

    public function hasDefault(): bool
    {
        return $this->default instanceOf Name;
    }

    public function default(): Name
    {
        return $this->default;
    }

    /**
     * @param mixed $value
     *
     * @throws InvalidArgument
     */
    public function validate($value): void
    {
        if (!$this->type->accepts($value)) {
            throw new InvalidArgument((string) $this->name);
        }
    }

    public function compile(): CompiledArgument
    {
        return new CompiledArgument($this);
    }
}
