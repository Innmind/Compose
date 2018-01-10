<?php
declare(strict_types = 1);

namespace Innmind\Compose\Definition\Service;

use Innmind\Compose\Definition\Name;

final class Argument
{
    private $name;
    private $unwind;
    private $primitive = false;
    private $value;

    private function __construct(Name $name = null, bool $unwind = false)
    {
        $this->name = $name;
        $this->unwind = $unwind;
    }

    public static function decorate(): self
    {
        return new self;
    }

    public static function variable(Name $name): self
    {
        return new self($name);
    }

    public static function unwind(Name $name): self
    {
        return new self($name, true);
    }

    public static function primitive($value): self
    {
        $self = new self;
        $self->primitive = true;
        $self->value = $value;

        return $self;
    }

    public function decorates(): bool
    {
        return is_null($this->name) && !$this->isPrimitive();
    }

    public function reference(): Name
    {
        return $this->name;
    }

    public function toUnwind(): bool
    {
        return $this->unwind;
    }

    public function isPrimitive(): bool
    {
        return $this->primitive;
    }

    public function value()
    {
        return $this->value;
    }
}
