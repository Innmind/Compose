<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose\Definition\Service\Argument;

use Innmind\Compose\{
    Definition\Service\Argument\Unwind,
    Definition\Service\Argument\HoldReference,
    Definition\Service\Argument,
    Definition\Service\Arguments as Args,
    Definition\Service\Constructor\Construct,
    Definition\Service,
    Definition\Name,
    Definition\Argument as Arg,
    Definition\Argument\Type\Primitive,
    Definition\Dependency,
    Services,
    Arguments,
    Dependencies,
    Exception\ValueNotSupported,
    Exception\ArgumentNotProvided,
    Compilation\Service\Argument\Unwind as CompiledUnwind
};
use Innmind\Immutable\{
    StreamInterface,
    Stream,
    Map,
    Str
};
use PHPUnit\Framework\TestCase;
use Fixture\Innmind\Compose\Iterator;

class UnwindTest extends TestCase
{
    public function testFromValue()
    {
        $argument = Unwind::fromValue('...$foo', new Args);

        $this->assertInstanceOf(Unwind::class, $argument);
        $this->assertInstanceOf(Argument::class, $argument);
        $this->assertInstanceOf(HoldReference::class, $argument);
        $this->assertInstanceOf(Name::class, $argument->reference());
        $this->assertSame('foo', (string) $argument->reference());
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
                Construct::fromString(Str::of('stdClass'))
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
                Construct::fromString(Str::of('stdClass'))
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
                    Construct::fromString(Str::of('stdClass'))
                ))->exposeAs(new Name('foo')),
                new Service(
                    new Name('baz'),
                    Construct::fromString(Str::of(\SplObjectStorage::class))
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
                Construct::fromString(Str::of('stdClass'))
            ))->exposeAs(new Name('foo')),
            new Service(
                new Name('bar'),
                Construct::fromString(Str::of(\SplObjectStorage::class))
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
                Construct::fromString(Str::of(\SplObjectStorage::class))
            )
        );

        $this->expectException(ArgumentNotProvided::class);

        Unwind::fromValue('...$baz', new Args)->resolve(
            Stream::of('mixed'),
            $services
        );
    }

    public function testResolveContainerDependency()
    {
        $services = new Services(
            new Arguments,
            new Dependencies(
                new Dependency(
                    new Name('inner'),
                    new Services(
                        new Arguments,
                        new Dependencies,
                        (new Service(
                            new Name('foo'),
                            Construct::fromString(Str::of(Iterator::class)),
                            new Argument\Primitive(24),
                            new Argument\Primitive(42),
                            new Argument\Primitive(66)
                        ))->exposeAs(new Name('bar'))
                    )
                )
            )
        );

        $value = Unwind::fromValue('...$inner.bar', new Args)->resolve(
            Stream::of('mixed'),
            $services
        );

        $this->assertInstanceOf(StreamInterface::class, $value);
        $this->assertSame('mixed', (string) $value->type());
        $this->assertCount(3, $value);
        $this->assertSame(
            [24, 42, 66],
            $value->toPrimitive()
        );
    }

    public function testCompile()
    {
        $this->assertInstanceOf(
            CompiledUnwind::class,
            Unwind::fromValue('...$foo', new Args)->compile()
        );
    }
}
