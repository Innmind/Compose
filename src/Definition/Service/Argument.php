<?php
declare(strict_types = 1);

namespace Innmind\Compose\Definition\Service;

final class Argument
{
    private $name;
    private $unwind;

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

    public function decorates(): bool
    {
        return is_null($this->name);
    }

    public function reference(): Name
    {
        return $this->name;
    }

    public function toUnwind(): bool
    {
        return $this->unwind;
    }
}
