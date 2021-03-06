<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose\Definition\Service\Constructor;

use Innmind\Compose\{
    Definition\Service\Constructor\Map,
    Definition\Service\Constructor\Construct,
    Definition\Service\Constructor,
    Definition\Service,
    Definition\Name,
    Services,
    Arguments,
    Dependencies,
    Lazy,
    Lazy\Map as LazyMap,
    Exception\ValueNotSupported,
    Compilation\Service\Constructor\Map as CompiledMap
};
use Innmind\Immutable\{
    Str,
    Pair
};
use PHPUnit\Framework\TestCase;

class MapTest extends TestCase
{
    public function testConstruct()
    {
        $construct = Map::fromString(Str::of('map<int, string>'));

        $this->assertInstanceOf(Constructor::class, $construct);
        $this->assertSame('map<int, string>', (string) $construct);

        $instance = $construct(
            new Pair(1, 'foo'),
            new Pair(2, 'bar')
        );

        $this->assertInstanceOf(LazyMap::class, $instance);
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

        $services = new Services(
            new Arguments,
            new Dependencies,
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
                Lazy::service(
                    new Name('foo'),
                    $services
                ),
                Lazy::service(
                    new Name('bar'),
                    $services
                )
            )
        );

        $this->assertInstanceOf(LazyMap::class, $instance);
        $this->assertCount(1, $instance);
        $this->assertTrue($instance->contains($services->build(new Name('foo'))));
        $this->assertSame(
            $services->build(new Name('bar')),
            $instance->get($services->build(new Name('foo')))
        );
    }

    public function testCompile()
    {
        $this->assertInstanceOf(
            CompiledMap::class,
            Map::fromString(Str::of('map<int, string>'))->compile()
        );
    }
}
