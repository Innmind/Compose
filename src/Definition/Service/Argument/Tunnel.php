<?php
declare(strict_types = 1);

namespace Innmind\Compose\Definition\Service\Argument;

use Innmind\Compose\{
    Definition\Name,
    Definition\Service\Argument,
    Definition\Service\Arguments,
    Services,
    Exception\LogicException
};
use Innmind\Immutable\StreamInterface;

final class Tunnel implements Argument
{
    private $dependency;
    private $argument;

    public function __construct(Name $dependency, Argument $argument)
    {
        $this->dependency = $dependency;
        $this->argument = $argument;
    }

    /**
     * {@inheritdoc}
     */
    public static function fromValue($value, Arguments $arguments): Argument
    {
        throw new LogicException('Can\'t be used outside Service::tunnel()');
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(
        StreamInterface $built,
        Services $services
    ): StreamInterface {
        return $services
            ->dependencies()
            ->extract($this->dependency, $built, $this->argument);
    }
}
