<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose\Compilation\Service\Argument;

use Innmind\Compose\{
    Compilation\Service\Argument\Primitive,
    Compilation\Service\Argument,
    Exception\LogicException
};
use PHPUnit\Framework\TestCase;

class PrimitiveTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Argument::class,
            new Primitive('foo')
        );
    }

    public function testMethod()
    {
        $argument = new Primitive('foo');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Primitive cannot be accessed from outside the compiled container');

        $argument->method();
    }

    public function testStringCast()
    {
        $argument = new Primitive('foo');

        $this->assertSame('\'foo\'', (string) $argument);
    }
}
