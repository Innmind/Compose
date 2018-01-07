<?php
declare(strict_types = 1);

namespace Innmind\Compose\Definition\Service;

use Innmind\Immutable\Str;

final class Constructor
{
    private $value;

    public function __construct(string $value)
    {
        $this->value = Str::of($value);
    }

    /**
     * @param mixed $arguments
     */
    public function __invoke(...$arguments): object
    {
        if ($this->value->matches('~\S+::\S+~')) {
            [$class, $method] = $this->value->split('::');

            return [(string) $class, (string) $method](...$arguments);
        }

        $class = (string) $this->value;

        return new $class(...$arguments);
    }
}
