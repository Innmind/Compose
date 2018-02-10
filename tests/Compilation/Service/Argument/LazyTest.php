<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose\Compilation\Service\Argument;

use Innmind\Compose\{
    Compilation\Service\Argument\Lazy,
    Compilation\Service\Argument\Reference,
    Compilation\Service\Argument,
    Compilation\MethodName,
    Definition\Name
};
use PHPUnit\Framework\TestCase;

class LazyTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Argument::class,
            new Lazy($this->createMock(Argument::class))
        );
    }

    public function testMethod()
    {
        $lazy = new Lazy($mock = $this->createMock(Argument::class));
        $mock
            ->expects($this->once())
            ->method('method')
            ->willReturn($expected = new MethodName(new Name('foo')));

        $this->assertSame($expected, $lazy->method());
    }

    public function testStringCast()
    {
        $lazy = new Lazy($mock = $this->createMock(Argument::class));
        $mock
            ->expects($this->once())
            ->method('__toString')
            ->willReturn('foo');

        $this->assertSame('foo', (string) $lazy);
    }

    public function testReferenceStringCast()
    {
        $lazy = new Lazy(new Reference(new Name('foo')));
        $expected = <<<PHP
new \\Innmind\\Compose\\Lazy(function() {
    return \$this->buildFoo();
})
PHP;

        $this->assertSame($expected, (string) $lazy);
    }
}
