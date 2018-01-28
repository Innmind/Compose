<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose\Definition\Service\Constructor;

use Innmind\Compose\{
    Definition\Service\Constructor\Map,
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
    Map as ImmutableMap,
    Pair
};
use PHPUnit\Framework\TestCase;

class MapTest extends TestCase
{
    public function testConstruct()
    {
        $construct = Map::fromString(Str::of('map<int, string>'));

        $this->assertInstanceOf(Constructor::class, $construct);

        $instance = $construct(
            new Pair(1, 'foo'),
            new Pair(2, 'bar')
        );

        $this->assertInstanceOf(ImmutableMap::class, $instance);
        $this->assertSame('int', (string) $instance->keyType());
        $this->assertSame('string', (string) $instance->valueType());
        $this->assertCount(2, $instance);
        $this->assertSame('foo', $instance->get(1));
        $this->assertSame('bar', $instance->get(2));
    }

    public function testThrowWhenNotOfExpectedFormat()
    {
        $this->expectException(ValueNotSupported::class);
        $this->expectExceptionMessage('foo');

        Map::fromString(Str::of('foo'));
    }

    public function testLoadLazyService()
    {
        $construct = Map::fromString(Str::of('map<stdClass, stdClass>'));

        $definitions = new Definitions(
            new Arguments,
            new Service(
                new Name('foo'),
                Construct::fromString(Str::of('stdClass'))
            ),
            new Service(
                new Name('bar'),
                Construct::fromString(Str::of('stdClass'))
            )
        );

        $instance = $construct(
            new Pair(
                new Lazy(
                    new Name('foo'),
                    $definitions
                ),
                new Lazy(
                    new Name('bar'),
                    $definitions
                )
            )
        );

        $this->assertInstanceOf(ImmutableMap::class, $instance);
        $this->assertCount(1, $instance);
        $this->assertTrue($instance->contains($definitions->build(new Name('foo'))));
        $this->assertSame(
            $definitions->build(new Name('bar')),
            $instance->get($definitions->build(new Name('foo')))
        );
    }
}
