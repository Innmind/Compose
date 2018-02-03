<?php
declare(strict_types = 1);

namespace Innmind\Compose\Definition;

use Innmind\Compose\Exception\{
    NameMustContainAtLeastACharacter,
    NameNotNamespaced
};
use Innmind\Immutable\Str;

final class Name
{
    private $value;

    public function __construct(string $value)
    {
        $value = Str::of($value);

        if ($value->empty()) {
            throw new NameMustContainAtLeastACharacter;
        }

        $this->value = $value;
    }

    public function add(self $name): self
    {
        return new self($this->value.'.'.$name);
    }

    public function root(): self
    {
        $namespace = $this->value->split('.');

        if ($namespace->size() === 1) {
            throw new NameNotNamespaced((string) $this->value);
        }

        return new self((string) $namespace->first());
    }

    public function withoutRoot(): self
    {
        $namespace = $this->value->split('.');

        if ($namespace->size() === 1) {
            throw new NameNotNamespaced((string) $this->value);
        }

        return new self((string) $namespace->drop(1)->join('.'));
    }

    public function equals(self $other): bool
    {
        return (string) $this === (string) $other;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
