<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose\Compilation\Service\Constructor;

use Innmind\Compose\Compilation\Service\{
  Constructor\ServiceFactory,
  Constructor,
  Argument,
};
use PHPUnit\Framework\TestCase;

class ServiceFactoryTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Constructor::class,
            new ServiceFactory('bar', $this->createMock(Argument::class))
        );
    }

    public function testStringCast()
    {
        $constructor = new ServiceFactory(
            'create',
            $factory = $this->createMock(Argument::class),
            $mock = $this->createMock(Argument::class)
        );
        $factory
            ->expects($this->once())
            ->method('__toString')
            ->willReturn('$this->getFactory()');
        $mock
            ->expects($this->once())
            ->method('__toString')
            ->willReturn('bar');
        $expected = <<<PHP
\$this->getFactory()->create(
bar
)
PHP;

        $this->assertSame($expected, (string) $constructor);
    }
}
