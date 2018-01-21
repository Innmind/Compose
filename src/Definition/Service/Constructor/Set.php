<?php
declare(strict_types = 1);

namespace Innmind\Compose\Definition\Service\Constructor;

use Innmind\Compose\{
    Definition\Service\Constructor,
    Exception\ValueNotSupported
};
use Innmind\Immutable\{
    Str,
    Set as ImmutableSet
};

final class Set implements Constructor
{
    private $type;
    private $method;

    private function __construct(string $type)
    {
        $this->type = $type;
    }

    /**
     * {@inheritdoc}
     */
    public static function fromString(Str $value): Constructor
    {
        if (!$value->matches('~^set<\S+>$~')) {
            throw new ValueNotSupported((string) $value);
        }

        $components = $value->capture('~^set<(?<type>\S+)>$~');

        return new self((string) $components->get('type'));
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(...$arguments): object
    {
        return ImmutableSet::of($this->type, ...$arguments);
    }
}
