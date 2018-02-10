<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose\Definition\Service\Constructor;

use Innmind\Compose\{
    Definition\Service\Constructor\Stream,
    Definition\Service\Constructor\Construct,
    Definition\Service\Constructor,
    Definition\Service,
    Definition\Name,
    Services,
    Arguments,
    Dependencies,
    Lazy,
    Lazy\Stream as LazyStream,
    Exception\ValueNotSupported,
    Compilation\Service\Constructor\Stream as CompiledStream
};
use Innmind\Immutable\Str;
use PHPUnit\Framework\TestCase;

class StreamTest extends TestCase
{
    public function testConstruct()
    {
        $construct = Stream::fromString(Str::of('stream<int>'));

        $this->assertInstanceOf(Constructor::class, $construct);
        $this->assertSame('stream<int>', (string) $construct);

        $instance = $construct(1, 2);

        $this->assertInstanceOf(LazyStream::class, $instance);
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
            Lazy::service(
                new Name('foo'),
                new Services(
                    new Arguments,
                    new Dependencies,
                    new Service(
                        new Name('foo'),
                        Construct::fromString(Str::of('stdClass'))
                    )
                )
            )
        );

        $this->assertInstanceOf(LazyStream::class, $instance);
        $this->assertCount(1, $instance);
        $this->assertInstanceOf('stdClass', $instance->current());
    }

    public function testCompile()
    {
        $this->assertInstanceOf(
            CompiledStream::class,
            Stream::fromString(Str::of('stream<int>'))->compile()
        );
    }
}
