<?php
declare(strict_types = 1);

namespace Innmind\Compose\Definition\Service\Constructor;

use Innmind\Compose\{
    Definition\Service\Constructor,
    Exception\ValueNotSupported,
    Lazy
};
use Innmind\Immutable\{
    Str,
    Sequence
};

final class Factory implements Constructor
{
    private $class;
    private $method;

    private function __construct(string $class, string $method)
    {
        $this->class = $class;
        $this->method = $method;
    }

    /**
     * {@inheritdoc}
     */
    public static function fromString(Str $value): Constructor
    {
        if (!$value->matches('~^\S+::\S+$~')) {
            throw new ValueNotSupported((string) $value);
        }

        [$class, $method] = $value->split('::');

        return new self((string) $class, (string) $method);
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(...$arguments): object
    {
        $arguments = Sequence::of(...$arguments)->map(static function($argument) {
            if ($argument instanceof Lazy) {
                return $argument->load();
            }

            return $argument;
        });

        return [$this->class, $this->method](...$arguments);
    }

    public function __toString(): string
    {
        return sprintf('%s::%s', $this->class, $this->method);
    }
}
