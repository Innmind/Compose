<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose\Compilation\Service\Constructor;

use Innmind\Compose\Compilation\Service\{
  Constructor\Merge,
  Constructor,
  Argument
};
use PHPUnit\Framework\TestCase;

class MergeTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Constructor::class,
            new Merge(
                $this->createMock(Argument::class),
                $this->createMock(Argument::class)
            )
        );
    }

    public function testStringCast()
    {
        $constructor = new Merge(
            $mock1 = $this->createMock(Argument::class),
            $mock2 = $this->createMock(Argument::class),
            $mock3 = $this->createMock(Argument::class)
        );
        $mock1
            ->expects($this->once())
            ->method('__toString')
            ->willReturn('foo');
        $mock2
            ->expects($this->once())
            ->method('__toString')
            ->willReturn('bar');
        $mock3
            ->expects($this->once())
            ->method('__toString')
            ->willReturn('baz');
        $expected = <<<PHP
foo
->merge(bar)
->merge(baz)
PHP;

        $this->assertSame($expected, (string) $constructor);
    }
}
