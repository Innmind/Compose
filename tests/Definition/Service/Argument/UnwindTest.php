<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose\Definition\Service\Argument;

use Innmind\Compose\{
    Definition\Service\Argument\Unwind,
    Definition\Service\Argument,
    Definition\Service\Arguments as Args,
    Definition\Service\Constructor,
    Definition\Service,
    Definition\Name,
    Definition\Argument as Arg,
    Definition\Argument\Type\Primitive,
    Services,
    Arguments,
    Dependencies,
    Exception\ValueNotSupported,
    Exception\ArgumentNotProvided
};
use Innmind\Immutable\{
    StreamInterface,
    Stream,
    Map,
    Str
};
use PHPUnit\Framework\TestCase;

class UnwindTest extends TestCase
{
    public function testFromValue()
    {
        $argument = Unwind::fromValue('...$foo', new Args);

        $this->assertInstanceOf(Unwind::class, $argument);
        $this->assertInstanceOf(Argument::class, $argument);
    }

    public function testThrowWhenNotAString()
    {
        $this->expectException(ValueNotSupported::class);

        Unwind::fromValue(42, new Args);
    }

    public function testThrowWhenNotAUnwind()
    {
        $this->expectException(ValueNotSupported::class);
        $this->expectExceptionMessage('foo');

        Unwind::fromValue('foo', new Args);
    }

    public function testResolveArgument()
    {
        $services = new Services(
            new Arguments(
                new Arg(
                    new Name('baz'),
                    new Primitive('array')
                )
            ),
            new Dependencies,
            (new Service(
                new Name('foo'),
                Constructor\Construct::fromString(Str::of('stdClass'))
            ))->exposeAs(new Name('foo'))
        );
        $services = $services->inject(Map::of(
            'string',
            'mixed',
            ['baz'],
            [[1, 2, 3]]
        ));

        $value = Unwind::fromValue('...$baz', new Args)->resolve(
            Stream::of('mixed'),
            $services
        );

        $this->assertInstanceOf(StreamInterface::class, $value);
        $this->assertSame('mixed', (string) $value->type());
        $this->assertCount(3, $value);
        $this->assertSame([1, 2, 3], $value->toPrimitive());
    }

    public function testResolveOptionalArgument()
    {
        $services = new Services(
            new Arguments(
                (new Arg(
                    new Name('baz'),
                    new Primitive('array')
                ))->makeOptional()
            ),
            new Dependencies,
            (new Service(
                new Name('foo'),
                Constructor\Construct::fromString(Str::of('stdClass'))
            ))->exposeAs(new Name('foo'))
        );
        $services = $services->inject(Map::of(
            'string',
            'mixed',
            [],
            []
        ));

        $value = Unwind::fromValue('...$baz', new Args)->resolve(
            Stream::of('mixed'),
            $services
        );

        $this->assertInstanceOf(StreamInterface::class, $value);
        $this->assertSame('mixed', (string) $value->type());
        $this->assertCount(0, $value);
    }

    public function testResolveDirectDepedency()
    {
        $value = Unwind::fromValue('...$baz', new Args)->resolve(
            Stream::of('mixed'),
            new Services(
                new Arguments,
                new Dependencies,
                (new Service(
                    new Name('foo'),
                    Constructor\Construct::fromString(Str::of('stdClass'))
                ))->exposeAs(new Name('foo')),
                new Service(
                    new Name('baz'),
                    Constructor\Construct::fromString(Str::of(\SplObjectStorage::class))
                )
            )
        );

        $this->assertInstanceOf(StreamInterface::class, $value);
        $this->assertSame('mixed', (string) $value->type());
        $this->assertCount(0, $value); //because spl object storage is empty
    }

    public function testResolveArgumentDefault()
    {
        $services = new Services(
            new Arguments(
                (new Arg(
                    new Name('baz'),
                    new Primitive('array')
                ))->defaultsTo(new Name('bar'))
            ),
            new Dependencies,
            (new Service(
                new Name('foo'),
                Constructor\Construct::fromString(Str::of('stdClass'))
            ))->exposeAs(new Name('foo')),
            new Service(
                new Name('bar'),
                Constructor\Construct::fromString(Str::of(\SplObjectStorage::class))
            )
        );
        $services = $services->inject(Map::of(
            'string',
            'mixed',
            [],
            []
        ));

        $value = Unwind::fromValue('...$baz', new Args)->resolve(
            Stream::of('mixed'),
            $services
        );

        $this->assertInstanceOf(StreamInterface::class, $value);
        $this->assertSame('mixed', (string) $value->type());
        $this->assertCount(0, $value); //because spl object storage is empty
    }

    public function testThrowWhenArgumentNotProvided()
    {
        $services = new Services(
            new Arguments(
                new Arg(
                    new Name('baz'),
                    new Primitive('array')
                )
            ),
            new Dependencies,
            new Service(
                new Name('bar'),
                Constructor\Construct::fromString(Str::of(\SplObjectStorage::class))
            )
        );

        $this->expectException(ArgumentNotProvided::class);

        Unwind::fromValue('...$baz', new Args)->resolve(
            Stream::of('mixed'),
            $services
        );
    }
}
