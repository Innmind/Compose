<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose\Definition\Service\Argument;

use Innmind\Compose\{
    Definition\Service\Argument\Reference,
    Definition\Service\Argument,
    Definition\Service\Arguments as Args,
    Definition\Service\Constructor,
    Definition\Service,
    Definition\Name,
    Definition\Argument as Arg,
    Definition\Argument\Type\Primitive,
    Definitions,
    Arguments,
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

class ReferenceTest extends TestCase
{
    public function testFromValue()
    {
        $argument = Reference::fromValue('$foo', new Args);

        $this->assertInstanceOf(Reference::class, $argument);
        $this->assertInstanceOf(Argument::class, $argument);
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
            new Definitions(
                new Arguments,
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
        $this->assertCount(1, $value);
        $this->assertInstanceOf(\SplObjectStorage::class, $value->current());
    }

    public function testResolveArgument()
    {
        $definitions = new Definitions(
            new Arguments(
                new Arg(
                    new Name('baz'),
                    new Primitive('array')
                )
            ),
            (new Service(
                new Name('foo'),
                Constructor\Construct::fromString(Str::of('stdClass'))
            ))->exposeAs(new Name('foo'))
        );
        $definitions = $definitions->inject(Map::of(
            'string',
            'mixed',
            ['baz'],
            [[1, 2, 3]]
        ));

        $value = Reference::fromValue('$baz', new Args)->resolve(
            Stream::of('mixed'),
            $definitions
        );

        $this->assertInstanceOf(StreamInterface::class, $value);
        $this->assertSame('mixed', (string) $value->type());
        $this->assertCount(1, $value);
        $this->assertSame([1, 2, 3], $value->current());
    }

    public function testResolveArgumentDefault()
    {
        $definitions = new Definitions(
            new Arguments(
                (new Arg(
                    new Name('baz'),
                    new Primitive('array')
                ))->defaultsTo(new Name('bar'))
            ),
            (new Service(
                new Name('foo'),
                Constructor\Construct::fromString(Str::of('stdClass'))
            ))->exposeAs(new Name('foo')),
            new Service(
                new Name('bar'),
                Constructor\Construct::fromString(Str::of(\SplObjectStorage::class))
            )
        );
        $definitions = $definitions->inject(Map::of(
            'string',
            'mixed',
            [],
            []
        ));

        $value = Reference::fromValue('$baz', new Args)->resolve(
            Stream::of('mixed'),
            $definitions
        );

        $this->assertInstanceOf(StreamInterface::class, $value);
        $this->assertSame('mixed', (string) $value->type());
        $this->assertCount(1, $value);
        $this->assertInstanceOf(\SplObjectStorage::class, $value->current());
    }

    public function testResolveOptionalArgument()
    {
        $definitions = new Definitions(
            new Arguments(
                (new Arg(
                    new Name('baz'),
                    new Primitive('array')
                ))->makeOptional()
            ),
            (new Service(
                new Name('foo'),
                Constructor\Construct::fromString(Str::of('stdClass'))
            ))->exposeAs(new Name('foo'))
        );
        $definitions = $definitions->inject(Map::of(
            'string',
            'mixed',
            [],
            []
        ));

        $value = Reference::fromValue('$baz', new Args)->resolve(
            Stream::of('mixed'),
            $definitions
        );

        $this->assertInstanceOf(StreamInterface::class, $value);
        $this->assertSame('mixed', (string) $value->type());
        $this->assertCount(1, $value);
        $this->assertNull($value->current());
    }

    public function testThrowWhenArgumentNotProvided()
    {
        $definitions = new Definitions(
            new Arguments(
                new Arg(
                    new Name('baz'),
                    new Primitive('array')
                )
            ),
            (new Service(
                new Name('foo'),
                Constructor\Construct::fromString(Str::of('stdClass'))
            ))->exposeAs(new Name('foo'))
        );

        $this->expectException(ArgumentNotProvided::class);

        Reference::fromValue('$baz', new Args)->resolve(
            Stream::of('mixed'),
            $definitions
        );
    }
}
