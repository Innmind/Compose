<?php
declare(strict_types = 1);

namespace Innmind\Compose\Definition\Service\Argument;

use Innmind\Compose\{
    Definition\Service\Argument,
    Definition\Service\Arguments,
    Definition\Name,
    Services,
    Compilation\Service\Argument as CompiledArgument,
    Compilation\Service\Argument\Pair as CompiledPair,
    Exception\ValueNotSupported,
    Exception\LogicException,
};
use Innmind\Immutable\{
    Pair as ImmutablePair,
    StreamInterface,
    Str,
    SetInterface,
    Set,
};

final class Pair implements Argument, HoldReferences
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
            ->first();
        $value = $this
            ->value
            ->resolve(
                $built->clear(),
                $services
            )
            ->first();

        return $built->add(new ImmutablePair($key, $value));
    }

    /**
     * {@inheritdoc}
     */
    public function references(): SetInterface
    {
        $references = Set::of(Name::class);

        if ($this->key instanceof HoldReference) {
            $references = $references->add($this->key->reference());
        }

        if ($this->value instanceof HoldReference) {
            $references = $references->add($this->value->reference());
        }

        return $references;
    }

    public function compile(): CompiledArgument
    {
        return new CompiledPair(
            $this->key->compile(),
            $this->value->compile()
        );
    }
}
