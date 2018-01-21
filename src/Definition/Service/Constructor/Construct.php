<?php
declare(strict_types = 1);

namespace Innmind\Compose\Definition\Service\Constructor;

use Innmind\Compose\Definition\Service\Constructor;
use Innmind\Immutable\Str;

final class Construct implements Constructor
{
    private $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    /**
     * {@inheritdoc}
     */
    public static function fromString(Str $value): Constructor
    {
        return new self((string) $value);
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(...$arguments): object
    {
        $class = (string) $this->value;

        return new $class(...$arguments);
    }
}
