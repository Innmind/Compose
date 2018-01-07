<?php
declare(strict_types = 1);

namespace Innmind\Compose\Definition\Argument;

use Innmind\Compose\Exception\NameMustBeAlphaNumeric;
use Innmind\Immutable\Str;

final class Name
{
    private $value;

    public function __construct(string $value)
    {
        if (!Str::of($value)->matches('~^[a-zA-Z0-9]+$~')) {
            throw new NameMustBeAlphaNumeric($value);
        }

        $this->value = $value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
