<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose\Definition\Service\Argument;

use Innmind\Compose\{
    Definition\Service\Argument\Reference,
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
    Lazy,
    Exception\ValueNotSupported,
    Exception\ArgumentNotProvided,
    Compilation\Service\Argument\Reference as CompiledReference
};
use Innmind\Immutable\{
    StreamInterface,
    Stream,
    Map,
    Str
};
use PHPUnit\Framework\TestCase;

class ReferenceTest extends TestCase
{
    public function testFromValue()
    {
        $argument = Reference::fromValue('$foo', new Args);

        $this->assertInstanceOf(Reference::class, $argument);
        $this->assertInstanceOf(Argument::class, $argument);
        $this->assertInstanceOf(HoldReference::class, $argument);
        $this->assertInstanceOf(Name::class, $argument->reference());
        $this->assertSame('foo', (string) $argument->reference());
    }

    public function testThrowWhenNotAString()
    {
        $this->expectException(ValueNotSupported::class);

        Reference::fromValue(42, new Args);
    }

    public function testThrowWhenNotAReference()
    {
        $this->expectException(ValueNotSupported::class);
        $this->expectExceptionMessage('foo');

        Reference::fromValue('foo', new Args);
    }

    public function testResolveDirectDepedency()
    {
        $value = Reference::fromValue('$baz', new Args)->resolve(
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
        $this->assertCount(1, $value);
        $this->assertInstanceOf(Lazy::class, $value->current());
        $this->assertInstanceOf(\SplObjectStorage::class, $value->current()->load());
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

        $value = Reference::fromValue('$baz', new Args)->resolve(
            Stream::of('mixed'),
            $services
        );

        $this->assertInstanceOf(StreamInterface::class, $value);
        $this->assertSame('mixed', (string) $value->type());
        $this->assertCount(1, $value);
        $this->assertSame([1, 2, 3], $value->current());
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

        $value = Reference::fromValue('$baz', new Args)->resolve(
            Stream::of('mixed'),
            $services
        );

        $this->assertInstanceOf(StreamInterface::class, $value);
        $this->assertSame('mixed', (string) $value->type());
        $this->assertCount(1, $value);
        $this->assertInstanceOf(Lazy::class, $value->current());
        $this->assertInstanceOf(\SplObjectStorage::class, $value->current()->load());
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

        $value = Reference::fromValue('$baz', new Args)->resolve(
            Stream::of('mixed'),
            $services
        );

        $this->assertInstanceOf(StreamInterface::class, $value);
        $this->assertSame('mixed', (string) $value->type());
        $this->assertCount(1, $value);
        $this->assertNull($value->current());
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
            (new Service(
                new Name('foo'),
                Construct::fromString(Str::of('stdClass'))
            ))->exposeAs(new Name('foo'))
        );

        $this->expectException(ArgumentNotProvided::class);

        Reference::fromValue('$baz', new Args)->resolve(
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
                            Construct::fromString(Str::of('stdClass'))
                        ))->exposeAs(new Name('bar'))
                    )
                )
            )
        );

        $value = Reference::fromValue('$inner.bar', new Args)->resolve(
            Stream::of('mixed'),
            $services
        );

        $this->assertInstanceOf(StreamInterface::class, $value);
        $this->assertSame('mixed', (string) $value->type());
        $this->assertCount(1, $value);
        $this->assertInstanceOf(Lazy::class, $value->current());
        $this->assertInstanceOf('stdClass', $value->current()->load());
    }

    public function testCompile()
    {
        $this->assertInstanceOf(
            CompiledReference::class,
            Reference::fromValue('$foo', new Args)->compile()
        );
    }
}
