<?php
declare(strict_types = 1);

namespace Innmind\Compose\Definition\Service;

final class Argument
{
    private $name;

    private function __construct(Name $name = null)
    {
        $this->name = $name;
    }

    public static function decorate(): self
    {
        return new self;
    }

    public static function variable(Name $name): self
    {
        return new self($name);
    }

    public function decorates(): bool
    {
        return is_null($this->name);
    }

    public function reference(): Name
    {
        return $this->name;
    }
}
