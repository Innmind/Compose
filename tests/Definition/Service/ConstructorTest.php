<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose\Definition\Service;

use Innmind\Compose\Definition\Service\Constructor;
use PHPUnit\Framework\TestCase;

class ConstructorTest extends TestCase
{
    public function testConstructViaStaticMethod()
    {
        $construct = new Constructor(Factory::class.'::foo');

        $instance = $construct(1, 2, 3);

        $this->assertInstanceOf(Factory::class, $instance);
        $this->assertSame([1, 2, 3], $instance->arguments);
    }

    public function testConstructViaConstruct()
    {
        $factory = new class {
            public $arguments;

            public function __construct(...$arguments)
            {
                $this->arguments = $arguments;
            }
        };
        $class = get_class($factory);

        $construct = new Constructor($class);

        $instance = $construct(1, 2, 3);

        $this->assertInstanceOf($class, $instance);
        $this->assertSame([1, 2, 3], $instance->arguments);
    }
}

class Factory
{
    public $arguments;

    public static function foo(...$arguments): self
    {
        $self = new self;
        $self->arguments = $arguments;

        return $self;
    }
}
