<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose\Compilation\Service\Constructor;

use Innmind\Compose\Compilation\Service\{
  Constructor\Factory,
  Constructor,
  Argument
};
use PHPUnit\Framework\TestCase;

class FactoryTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Constructor::class,
            new Factory('stdClass', 'bar')
        );
    }

    public function testStringCast()
    {
        $constructor = new Factory(
            'stdClass',
            'create',
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
\stdClass::create(
foo,
bar
)
PHP;

        $this->assertSame($expected, (string) $constructor);
    }
}
