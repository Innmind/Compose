<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose\Definition\Argument\Type;

use Innmind\Compose\{
    Definition\Argument\Type\Primitive,
    Definition\Argument\Type,
    Exception\NotAPrimitiveType,
    Exception\ValueNotSupported
};
use Innmind\Immutable\Str;
use PHPUnit\Framework\TestCase;

class PrimitiveTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(Type::class, new Primitive('int'));
    }

    public function testAccepts()
    {
        $type = new Primitive('int');

        $this->assertTrue($type->accepts(42));
        $this->assertFalse($type->accepts('42'));
    }

    public function testThrowWhenNotAPrimitiveType()
    {
        $this->expectException(NotAPrimitiveType::class);
        $this->expectExceptionMessage('foo');

        new Primitive('foo');
    }

    public function testFromString()
    {
        $this->assertTrue(
            Primitive::fromString(Str::of('int'))->accepts(42)
        );
        $this->assertTrue(
            Primitive::fromString(Str::of('integer'))->accepts(42)
        );
    }

    public function testThrowWhentNotSupported()
    {
        $this->expectException(ValueNotSupported::class);
        $this->expectExceptionMessage('foo');

        Primitive::fromString(Str::of('foo'));
    }
}
