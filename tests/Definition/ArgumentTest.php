<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose\Definition;

use Innmind\Compose\{
    Definition\Argument,
    Definition\Argument\Name,
    Definition\Argument\Type,
    Definition\Service\Name as ServiceName,
    Exception\InvalidArgument
};
use PHPUnit\Framework\TestCase;

class ArgumentTest extends TestCase
{
    public function testInterface()
    {
        $argument = new Argument(
            $name = new Name('foo'),
            $this->createMock(Type::class)
        );

        $this->assertSame($name, $argument->name());
    }

    public function testOptionalArgument()
    {
        $argument = new Argument(
            new Name('foo'),
            $this->createMock(Type::class)
        );

        $argument2 = $argument->makeOptional();

        $this->assertInstanceOf(Argument::class, $argument2);
        $this->assertNotSame($argument, $argument2);
        $this->assertFalse($argument->optional());
        $this->assertTrue($argument2->optional());
    }

    public function testDefineDefaultValue()
    {
        $argument = new Argument(
            new Name('foo'),
            $this->createMock(Type::class)
        );

        $argument2 = $argument->defaultsTo($name = new ServiceName('bar'));

        $this->assertInstanceOf(Argument::class, $argument2);
        $this->assertNotSame($argument, $argument2);
        $this->assertFalse($argument->optional());
        $this->assertFalse($argument->hasDefault());
        $this->assertFalse($argument2->optional());
        $this->assertTrue($argument2->hasDefault());
        $this->assertSame($name, $argument2->default());

        $this->expectException(\TypeError::class);
        $argument->default();
    }

    public function testValidateValue()
    {
        $argument = new Argument(
            new Name('foo'),
            $type = $this->createMock(Type::class)
        );
        $type
            ->expects($this->at(0))
            ->method('accepts')
            ->with(42)
            ->willReturn(true);
        $type
            ->expects($this->at(1))
            ->method('accepts')
            ->with('42')
            ->willReturn(false);

        $this->assertNull($argument->validate(42));

        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('foo');

        $argument->validate('42');
    }
}
