<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose\Definition\Argument\Type;

use Innmind\Compose\{
    Definition\Argument\Type\Stream,
    Definition\Argument\Type,
    Exception\ValueNotSupported
};
use Innmind\Immutable\{
    Stream as S,
    Str
};
use PHPUnit\Framework\TestCase;

class StreamTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(Type::class, new Stream('foo'));
        $this->assertSame('stream<foo>', (string) new Stream('foo'));
    }

    public function testAccepts()
    {
        $type = new Stream('stdClass');

        $this->assertTrue($type->accepts(new S('stdClass')));
        $this->assertFalse($type->accepts(new S('foo')));
        $this->assertFalse($type->accepts(new \stdClass));
    }

    public function testFromString()
    {
        $this->assertTrue(
            Stream::fromString(Str::of('stream<int>'))->accepts(new S('int'))
        );
    }

    public function testThrowWhenNotSupported()
    {
        $this->expectException(ValueNotSupported::class);
        $this->expectExceptionMessage('stream');

        Stream::fromString(Str::of('stream'));
    }
}
