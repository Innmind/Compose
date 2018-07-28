<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose\Lazy;

use Innmind\Compose\{
    Lazy\Map,
    Lazy,
    Services,
    Arguments,
    Dependencies,
    Definition\Name,
    Definition\Service,
    Definition\Service\Constructor\Construct
};
use Innmind\Immutable\{
    MapInterface,
    Map as Base,
    Str,
    Pair,
    SetInterface,
    StreamInterface,
    Exception\ElementNotFoundException,
    Exception\LogicException
};
use PHPUnit\Framework\TestCase;

class MapTest extends TestCase
{
    private $map;
    private $services;

    public function setUp()
    {
        $this->services = new Services(
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
        $this->map = Map::of(
            'stdClass',
            'stdClass',
            new Pair(
                Lazy::service(
                    new Name('foo'),
                    $this->services
                ),
                Lazy::service(
                    new Name('bar'),
                    $this->services
                )
            ),
            new Pair(
                Lazy::service(
                    new Name('bar'),
                    $this->services
                ),
                Lazy::service(
                    new Name('foo'),
                    $this->services
                )
            )
        );
    }

    public function testInterface()
    {
        $this->assertInstanceOf(
            MapInterface::class,
            $this->map
        );
    }

    public function testSize()
    {
        $this->assertSame(2, $this->map->size());
    }

    public function testCount()
    {
        $this->assertCount(2, $this->map);
    }

    public function testIterator()
    {
        $this->assertSame($this->foo(), $this->map->key());
        $this->assertSame($this->bar(), $this->map->current());
        $this->assertTrue($this->map->valid());
        $this->assertNull($this->map->next());
        $this->assertSame($this->bar(), $this->map->key());
        $this->assertSame($this->foo(), $this->map->current());
        $this->assertTrue($this->map->valid());
        $this->map->next();
        $this->assertFalse($this->map->valid());
        $this->assertNull($this->map->rewind());
        $this->assertSame($this->foo(), $this->map->key());
    }

    public function testArrayAccess()
    {
        $this->assertTrue(isset($this->map[$this->foo()]));
        $this->assertSame($this->bar(), $this->map[$this->foo()]);

        $this->expectException(ElementNotFoundException::class);

        $this->map[new \stdClass];
    }

    public function testThrowWhenTryingToSet()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('You can\'t modify a map');

        $this->map[new \stdClass] = new \stdClass;
    }

    public function testThrowWhenTryingToUnset()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('You can\'t modify a map');

        unset($this->map[$this->foo()]);
    }

    public function testPut()
    {
        $map = $this->map->put(
            $key = new \stdClass,
            $value = new \stdClass
        );

        $this->assertImmutability($map);
        $this->assertInstanceOf(Base::class, $map);
        $this->assertSame('stdClass', (string) $map->keyType());
        $this->assertSame('stdClass', (string) $map->valueType());
        $this->assertCount(3, $map);
        $this->assertSame($value, $map->get($key));
        $this->assertSame($this->bar(), $map->get($this->foo()));
        $this->assertSame($this->foo(), $map->get($this->bar()));
    }

    public function testGet()
    {
        $this->assertSame($this->bar(), $this->map->get($this->foo()));
        $this->assertSame($this->foo(), $this->map->get($this->bar()));
    }

    public function testContains()
    {
        $this->assertFalse($this->map->contains(new \stdClass));
        $this->assertTrue($this->map->contains($this->foo()));
    }

    public function testClear()
    {
        $map = $this->map->clear();

        $this->assertImmutability($map);
        $this->assertInstanceOf(Base::class, $map);
        $this->assertSame('stdClass', (string) $map->keyType());
        $this->assertSame('stdClass', (string) $map->valueType());
        $this->assertCount(0, $map);
    }

    public function testEquals()
    {
        $this->assertFalse($this->map->equals(new Base('stdClass', 'stdClass')));
        $this->assertTrue($this->map->equals(
            Map::of('stdClass', 'stdClass')
                ->put($this->bar(), $this->foo())
                ->put($this->foo(), $this->bar())
        ));
    }

    public function testFilter()
    {
        $map = $this->map->filter(function(\stdClass $key, \stdClass $value): bool {
            return $key === $this->foo();
        });

        $this->assertImmutability($map);
        $this->assertInstanceOf(MapInterface::class, $map);
        $this->assertSame('stdClass', (string) $map->keyType());
        $this->assertSame('stdClass', (string) $map->valueType());
        $this->assertCount(1, $map);
        $this->assertSame($this->bar(), $map->get($this->foo()));
    }

    public function testForeach()
    {
        $map = $this->map->foreach(function($key, $value): void {
            $this->assertInstanceOf('stdClass', $key);
            $this->assertInstanceOf('stdClass', $value);
        });

        $this->assertImmutability($map);
        $this->assertInstanceOf(Base::class, $map);
        $this->assertSame('stdClass', (string) $map->keyType());
        $this->assertSame('stdClass', (string) $map->valueType());
        $this->assertCount(2, $map);
    }

    public function testGroupBy()
    {
        $map = $this->map->groupBy(static function(\stdClass $key, \stdClass $value) {
            return $key;
        });

        $this->assertImmutability();
        $this->assertInstanceOf(Base::class, $map);
        $this->assertSame('stdClass', (string) $map->keyType());
        $this->assertSame(MapInterface::class, (string) $map->valueType());
        $this->assertCount(2, $map);
        $this->assertSame('stdClass', (string) $map->get($this->foo())->keyType());
        $this->assertSame('stdClass', (string) $map->get($this->foo())->valueType());
        $this->assertSame('stdClass', (string) $map->get($this->bar())->keyType());
        $this->assertSame('stdClass', (string) $map->get($this->bar())->valueType());
        $this->assertCount(1, $map->get($this->foo()));
        $this->assertCount(1, $map->get($this->bar()));
        $this->assertSame($this->bar(), $map->get($this->foo())->get($this->foo()));
        $this->assertSame($this->foo(), $map->get($this->bar())->get($this->bar()));
    }

    public function testKeys()
    {
        $keys = $this->map->keys();

        $this->assertInstanceOf(SetInterface::class, $keys);
        $this->assertSame('stdClass', (string) $keys->type());
        $this->assertSame(
            [$this->foo(), $this->bar()],
            $keys->toPrimitive()
        );
    }

    public function testValues()
    {
        $values = $this->map->values();

        $this->assertInstanceOf(StreamInterface::class, $values);
        $this->assertSame('stdClass', (string) $values->type());
        $this->assertSame(
            [$this->bar(), $this->foo()],
            $values->toPrimitive()
        );
    }

    public function testMap()
    {
        $map = $this->map->map(static function(\stdClass $key, \stdClass $value) {
            return clone $value;
        });

        $this->assertImmutability($map);
        $this->assertInstanceOf(Base::class, $map);
        $this->assertSame('stdClass', (string) $map->keyType());
        $this->assertSame('stdClass', (string) $map->valueType());
        $this->assertCount(2, $map);
        $this->assertInstanceOf('stdClass', $map->get($this->foo()));
        $this->assertInstanceOf('stdClass', $map->get($this->bar()));
    }

    public function testJoin()
    {
        $str = Map::of(
            'int',
            'string',
            new Pair(1, 'foo'),
            new Pair(2, 'bar')
        )->join(',');

        $this->assertInstanceOf(Str::class, $str);
        $this->assertSame('foo,bar', (string) $str);
    }

    public function testRemove()
    {
        $map = $this->map->remove($this->bar());

        $this->assertImmutability($map);
        $this->assertInstanceOf(Base::class, $map);
        $this->assertSame('stdClass', (string) $map->keyType());
        $this->assertSame('stdClass', (string) $map->valueType());
        $this->assertCount(1, $map);
        $this->assertSame($this->bar(), $map->get($this->foo()));
    }

    public function testMerge()
    {
        $map = $this->map->merge(
            (new Map('stdClass', 'stdClass'))
                ->put(
                    $key = new \stdClass,
                    $value = new \stdClass
                )
                ->put(
                    $this->foo(),
                    $this->foo()
                )
        );

        $this->assertImmutability($map);
        $this->assertInstanceOf(Base::class, $map);
        $this->assertSame('stdClass', (string) $map->keyType());
        $this->assertSame('stdClass', (string) $map->valueType());
        $this->assertCount(3, $map);
        $this->assertSame($this->foo(), $map->get($this->foo()));
        $this->assertSame($this->foo(), $map->get($this->bar()));
        $this->assertSame($value, $map->get($key));
    }

    public function testPartition()
    {
        $map = $this->map->partition(function(\stdClass $key, \stdClass $value): bool {
            return $key === $this->foo();
        });

        $this->assertImmutability();
        $this->assertInstanceOf(Base::class, $map);
        $this->assertSame('bool', (string) $map->keyType());
        $this->assertSame(MapInterface::class, (string) $map->valueType());
        $this->assertCount(2, $map);
        $this->assertSame('stdClass', (string) $map->get(true)->keyType());
        $this->assertSame('stdClass', (string) $map->get(true)->valueType());
        $this->assertSame('stdClass', (string) $map->get(false)->keyType());
        $this->assertSame('stdClass', (string) $map->get(false)->valueType());
        $this->assertCount(1, $map->get(true));
        $this->assertCount(1, $map->get(false));
        $this->assertSame($this->bar(), $map->get(true)->get($this->foo()));
        $this->assertSame($this->foo(), $map->get(false)->get($this->bar()));
    }

    public function testReduce()
    {
        $value = $this->map->reduce(
            [],
            static function(array $values, $key, $value): array {
                $values[] = $value;

                return $values;
            }
        );

        $this->assertImmutability();
        $this->assertSame([$this->bar(), $this->foo()], $value);
    }

    public function testMergeNonLoadedMaps()
    {
        $map = Map::of(
            'stdClass',
            'stdClass',
            new Pair(
                Lazy::service(
                    new Name('foo'),
                    $this->services
                ),
                Lazy::service(
                    new Name('bar'),
                    $this->services
                )
            ),
            new Pair(
                Lazy::service(
                    new Name('bar'),
                    $this->services
                ),
                Lazy::service(
                    new Name('foo'),
                    $this->services
                )
            )
        );

        $newMap = $this->map->merge($map);

        $this->assertCount(2, $map);
        $this->assertSame($this->bar(), $newMap->get($this->foo()));
    }

    private function assertImmutability(MapInterface $map = null): void
    {
        if ($map) {
            $this->assertNotSame($this->map, $map);
        }

        $this->assertInstanceOf(Map::class, $this->map);
        $this->assertSame('stdClass', (string) $this->map->keyType());
        $this->assertSame('stdClass', (string) $this->map->valueType());
        $this->assertSame($this->bar(), $this->map->get($this->foo()));
        $this->assertSame($this->foo(), $this->map->get($this->bar()));
    }

    private function foo()
    {
        return $this->services->build(new Name('foo'));
    }

    private function bar()
    {
        return $this->services->build(new Name('bar'));
    }
}
