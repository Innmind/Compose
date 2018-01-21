<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose\Definition\Service\Constructor;

use Innmind\Compose\{
    Definition\Service\Constructor\Stream,
    Definition\Service\Constructor,
    Exception\ValueNotSupported
};
use Innmind\Immutable\{
    Str,
    Stream as ImmutableStream
};
use PHPUnit\Framework\TestCase;

class StreamTest extends TestCase
{
    public function testConstruct()
    {
        $construct = Stream::fromString(Str::of('stream<int>'));

        $this->assertInstanceOf(Constructor::class, $construct);

        $instance = $construct(1, 2);

        $this->assertInstanceOf(ImmutableStream::class, $instance);
        $this->assertSame('int', (string) $instance->type());
        $this->assertCount(2, $instance);
        $this->assertSame([1, 2], $instance->toPrimitive());
    }

    public function testThrowWhenNotOfExpectedFormat()
    {
        $this->expectException(ValueNotSupported::class);
        $this->expectExceptionMessage('foo');

        Stream::fromString(Str::of('foo'));
    }
}
