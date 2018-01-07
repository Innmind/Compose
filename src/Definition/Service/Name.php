<?php
declare(strict_types = 1);

namespace Innmind\Compose\Definition\Service;

use Innmind\Compose\Exception\NameMustContainAtLeastACharacter;
use Innmind\Immutable\Str;

final class Name
{
    private $value;

    public function __construct(string $value)
    {
        if (Str::of($value)->empty()) {
            throw new NameMustContainAtLeastACharacter;
        }

        $this->value = $value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
