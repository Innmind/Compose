<?php
declare(strict_types = 1);

namespace Innmind\Compose\Definition\Service\Constructor;

use Innmind\Compose\{
    Definition\Service\Constructor,
    Lazy
};
use Innmind\Immutable\{
    Str,
    Sequence
};

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

        $arguments = Sequence::of(...$arguments)->map(static function($argument) {
            if ($argument instanceof Lazy) {
                return $argument->load();
            }

            return $argument;
        });

        return new $class(...$arguments);
    }
}
