<?php
declare(strict_types = 1);

namespace Innmind\Compose\Definition\Service\Argument;

use Innmind\Compose\{
    Definition\Service\Argument,
    Definition\Service\Arguments,
    Services
};
use Innmind\Immutable\StreamInterface;

final class Primitive implements Argument
{
    private $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * {@inheritdoc}
     */
    public static function fromValue($value, Arguments $arguments): Argument
    {
        return new self($value);
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(
        StreamInterface $built,
        Services $services
    ): StreamInterface {
        return $built->add($this->value);
    }
}
