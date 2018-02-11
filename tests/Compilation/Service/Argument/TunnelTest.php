<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose\Compilation\Service\Argument;

use Innmind\Compose\{
    Compilation\Service\Argument\Tunnel,
    Compilation\Service\Argument,
    Compilation\MethodName,
    Definition\Name,
    Exception\LogicException
};
use PHPUnit\Framework\TestCase;

class TunnelTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Argument::class,
            new Tunnel(
                new Name('depFoo'),
                new MethodName(new Name('foo'))
            )
        );
    }

    public function testMethod()
    {
        $argument = new Tunnel(
            new Name('depFoo'),
                new MethodName(new Name('foo'))
        );

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Tunnel cannot be accessed from outside the compiled container');

        $argument->method();
    }

    public function testStringCast()
    {
        $argument = new Tunnel(
            new Name('depFoo'),
            new MethodName(new Name('foo'))
        );

        $this->assertSame('$this->depFoo->buildFoo()', (string) $argument);
    }
}
