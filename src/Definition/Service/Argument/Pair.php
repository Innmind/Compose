<?php
declare(strict_types = 1);

namespace Innmind\Compose\Definition\Service\Argument;

use Innmind\Compose\{
    Definition\Service\Argument,
    Definition\Service\Arguments,
    Services,
    Exception\ValueNotSupported,
    Exception\LogicException
};
use Innmind\Immutable\{
    Pair as ImmutablePair,
    StreamInterface,
    Str
};

final class Pair implements Argument
{
    private $key;
    private $value;

    private function __construct(Argument $key, Argument $value)
    {
        if ($key instanceof Unwind || $value instanceof Unwind) {
            throw new LogicException;
        }

        $this->key = $key;
        $this->value = $value;
    }

    /**
     * {@inheritdoc}
     */
    public static function fromValue($value, Arguments $arguments): Argument
    {
        if (!is_string($value)) {
            throw new ValueNotSupported;
        }

        $value = Str::of($value);

        if (!$value->matches('~^<\S+, ?\S+>$~')) {
            throw new ValueNotSupported((string) $value);
        }

        $components = $value->capture('~^<(?<key>\S+), ?(?<value>\S+)>$~');

        return new self(
            $arguments->load((string) $components->get('key')),
            $arguments->load((string) $components->get('value'))
        );
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(
        StreamInterface $built,
        Services $services
    ): StreamInterface {
        $key = $this
            ->key
            ->resolve(
                $built->clear(),
                $services
            )
            ->current();
        $value = $this
            ->value
            ->resolve(
                $built->clear(),
                $services
            )
            ->current();

        return $built->add(new ImmutablePair($key, $value));
    }
}
