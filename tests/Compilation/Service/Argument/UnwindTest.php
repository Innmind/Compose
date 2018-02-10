<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose\Compilation\Service\Argument;

use Innmind\Compose\{
    Compilation\Service\Argument\Unwind,
    Compilation\Service\Argument,
    Compilation\MethodName,
    Definition\Name
};
use PHPUnit\Framework\TestCase;

class UnwindTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Argument::class,
            new Unwind(new Name('foo'))
        );
    }

    public function testMethod()
    {
        $argument = new Unwind(new Name('foo'));

        $this->assertInstanceOf(MethodName::class, $argument->method());
        $this->assertSame('buildFoo', (string) $argument->method());
    }

    public function testStringCast()
    {
        $argument = new Unwind(new Name('foo'));

        $this->assertSame('...$this->buildFoo()', (string) $argument);
    }
}
