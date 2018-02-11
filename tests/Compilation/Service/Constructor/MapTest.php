<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose\Compilation\Service\Constructor;

use Innmind\Compose\Compilation\Service\{
  Constructor\Map,
  Constructor,
  Argument
};
use PHPUnit\Framework\TestCase;

class MapTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Constructor::class,
            new Map('stdClass', 'bar')
        );
    }

    public function testStringCast()
    {
        $constructor = new Map(
            'stdClass',
            'bar',
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
\\Innmind\\Compose\\Lazy\\Map::of(
    'stdClass',
    'bar',
    foo,
bar
)
PHP;

        $this->assertSame($expected, (string) $constructor);
    }
}
