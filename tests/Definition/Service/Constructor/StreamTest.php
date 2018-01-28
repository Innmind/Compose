<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose\Definition\Service\Constructor;

use Innmind\Compose\{
    Definition\Service\Constructor\Stream,
    Definition\Service\Constructor\Construct,
    Definition\Service\Constructor,
    Definition\Service,
    Definition\Name,
    Definitions,
    Arguments,
    Lazy,
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

    public function testLoadLazyService()
    {
        $construct = Stream::fromString(Str::of('stream<stdClass>'));

        $instance = $construct(
            new Lazy(
                new Name('foo'),
                new Definitions(
                    new Arguments,
                    new Service(
                        new Name('foo'),
                        Construct::fromString(Str::of('stdClass'))
                    )
                )
            )
        );

        $this->assertInstanceOf(ImmutableStream::class, $instance);
        $this->assertCount(1, $instance);
    }
}
