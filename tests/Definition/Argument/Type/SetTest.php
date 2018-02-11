<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose\Definition\Argument\Type;

use Innmind\Compose\{
    Definition\Argument\Type\Set,
    Definition\Argument\Type,
    Exception\ValueNotSupported
};
use Innmind\Immutable\{
    Set as S,
    Str
};
use PHPUnit\Framework\TestCase;

class SetTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(Type::class, new Set('foo'));
        $this->assertSame('set<foo>', (string) new Set('foo'));
    }

    public function testAccepts()
    {
        $type = new Set('stdClass');

        $this->assertTrue($type->accepts(new S('stdClass')));
        $this->assertFalse($type->accepts(new S('foo')));
        $this->assertFalse($type->accepts(new \stdClass));
    }

    public function testFromString()
    {
        $this->assertTrue(
            Set::fromString(Str::of('set<int>'))->accepts(new S('int'))
        );
    }

    public function testThrowWhenNotSupported()
    {
        $this->expectException(ValueNotSupported::class);
        $this->expectExceptionMessage('set');

        Set::fromString(Str::of('set'));
    }
}
