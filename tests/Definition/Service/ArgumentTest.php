<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose\Definition\Service;

use Innmind\Compose\Definition\Service\{
    Argument,
    Name
};
use PHPUnit\Framework\TestCase;

class ArgumentTest extends TestCase
{
    public function testDecorate()
    {
        $argument = Argument::decorate();

        $this->assertInstanceOf(Argument::class, $argument);
        $this->assertTrue($argument->decorates());

        $this->expectException(\TypeError::class);
        $argument->reference();
    }

    public function testVariable()
    {
        $name = new Name('foo');
        $argument = Argument::variable($name);

        $this->assertInstanceOf(Argument::class, $argument);
        $this->assertFalse($argument->decorates());
        $this->assertSame($name, $argument->reference());
    }
}
