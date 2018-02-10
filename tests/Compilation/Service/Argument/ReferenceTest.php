<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose\Compilation\Service\Argument;

use Innmind\Compose\{
    Compilation\Service\Argument\Reference,
    Compilation\Service\Argument,
    Compilation\MethodName,
    Definition\Name
};
use PHPUnit\Framework\TestCase;

class ReferenceTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Argument::class,
            new Reference(new Name('foo'))
        );
    }

    public function testMethod()
    {
        $argument = new Reference(new Name('foo'));

        $this->assertInstanceOf(MethodName::class, $argument->method());
        $this->assertSame('buildFoo', (string) $argument->method());
    }

    public function testStringCast()
    {
        $argument = new Reference(new Name('foo'));

        $this->assertSame('$this->buildFoo()', (string) $argument);
    }
}
