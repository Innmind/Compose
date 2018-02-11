<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose\Compilation\Service\Constructor;

use Innmind\Compose\Compilation\Service\{
  Constructor\Construct,
  Constructor,
  Argument
};
use PHPUnit\Framework\TestCase;

class ConstructTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Constructor::class,
            new Construct('stdClass')
        );
    }

    public function testStringCast()
    {
        $constructor = new Construct(
            'stdClass',
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
new \stdClass(
foo,
bar
)
PHP;

        $this->assertSame($expected, (string) $constructor);
    }
}
