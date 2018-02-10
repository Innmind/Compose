<?php
declare(strict_types = 1);

namespace Innmind\Compose\Compilation\Service\Constructor;

use Innmind\Compose\{
    Compilation\Service\Constructor,
    Compilation\Service\Argument
};
use Innmind\Immutable\Stream;

final class Map implements Constructor
{
    private $key;
    private $value;
    private $arguments;

    public function __construct(string $key, string $value, Argument ...$arguments)
    {
        $this->key = $key;
        $this->value = $value;
        $this->arguments = Stream::of(Argument::class, ...$arguments);
    }

    public function __toString(): string
    {
        $code = <<<PHP
\\Innmind\\Compose\\Lazy\\Map::of(
    '{$this->key}',
    '{$this->value}',
    {$this->arguments->join(",\n")}
)
PHP;

        return $code;
    }
}
