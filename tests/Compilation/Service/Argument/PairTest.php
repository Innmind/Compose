<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose\Compilation\Service\Argument;

use Innmind\Compose\{
    Compilation\Service\Argument\Pair,
    Compilation\Service\Argument,
    Exception\LogicException
};
use PHPUnit\Framework\TestCase;

class PairTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Argument::class,
            new Pair(
                $this->createMock(Argument::class),
                $this->createMock(Argument::class)
            )
        );
    }

    public function testMethod()
    {
        $argument = new Pair(
            $this->createMock(Argument::class),
            $this->createMock(Argument::class)
        );

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Argument pair cannot be accessed from outside the compiled container');

        $argument->method();
    }

    public function testStringCast()
    {
        $argument = new Pair(
            $mock1 = $this->createMock(Argument::class),
            $mock2 = $this->createMock(Argument::class)
        );
        $mock1
            ->expects($this->once())
            ->method('__toString')
            ->willReturn('foo');
        $mock2
            ->expects($this->once())
            ->method('__toString')
            ->willReturn('bar');
        $expected = <<<PHP
new \\Innmind\\Immutable\\Pair(
    foo,
    bar
)
PHP;

        $this->assertSame($expected, (string) $argument);
    }
}
