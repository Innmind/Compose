<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose\Lazy;

use Innmind\Compose\{
    Lazy\Set,
    Lazy,
    Services,
    Arguments,
    Dependencies,
    Definition\Name,
    Definition\Service,
    Definition\Service\Constructor\Construct
};
use Innmind\Immutable\{
    SetInterface,
    Set as Base,
    Str,
    MapInterface,
    StreamInterface
};
use PHPUnit\Framework\TestCase;

class SetTest extends TestCase
{
    private $set;
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
            ),
            new Service(
                new Name('baz'),
                Construct::fromString(Str::of('stdClass'))
            )
        );
        $this->set = Set::of(
            'stdClass',
            Lazy::service(
                new Name('foo'),
                $this->services
            ),
            Lazy::service(
                new Name('bar'),
                $this->services
            ),
            Lazy::service(
                new Name('baz'),
                $this->services
            )
        );
    }

    public function testInterface()
    {
        $this->assertInstanceOf(SetInterface::class, $this->set);
    }

    public function testSize()
    {
        $this->assertSame(3, $this->set->size());
    }

    public function testCount()
    {
        $this->assertCount(3, $this->set);
    }

    public function testToPrimitive()
    {
        $this->assertSame(
            [$this->foo(), $this->bar(), $this->baz()],
            $this->set->toPrimitive()
        );
    }

    public function testIterator()
    {
        $this->assertSame(0, $this->set->key());
        $this->assertSame($this->foo(), $this->set->current());
        $this->assertTrue($this->set->valid());
        $this->assertNull($this->set->next());
        $this->assertSame(1, $this->set->key());
        $this->assertSame($this->bar(), $this->set->current());
        $this->assertTrue($this->set->valid());
        $this->set->next();
        $this->assertSame(2, $this->set->key());
        $this->assertSame($this->baz(), $this->set->current());
        $this->assertTrue($this->set->valid());
        $this->set->next();
        $this->assertFalse($this->set->valid());
        $this->assertNull($this->set->rewind());
        $this->assertSame(0, $this->set->key());
    }

    public function testIntersect()
    {
        $set = $this->set->intersect(Base::of('stdClass', $this->bar()));

        $this->assertImmutability($set);
        $this->assertInstanceOf(Base::class, $set);
        $this->assertSame('stdClass', (string) $set->type());
        $this->assertSame([$this->bar()], $set->toPrimitive());
    }

    public function testAdd()
    {
        $set = $this->set->add($expected = new \stdClass);

        $this->assertImmutability($set);
        $this->assertInstanceOf(Base::class, $set);
        $this->assertSame('stdClass', (string) $set->type());
        $this->assertSame(
            [$this->foo(), $this->bar(), $this->baz(), $expected],
            $set->toPrimitive()
        );

        $this->assertSame(
            $this->set->toPrimitive(),
            $this->set->add($this->foo())->toPrimitive()
        );
    }

    public function testContains()
    {
        $this->assertFalse($this->set->contains(new \stdClass));
        $this->assertTrue($this->set->contains($this->foo()));
    }

    public function testRemove()
    {
        $set = $this->set->remove($this->bar());

        $this->assertImmutability($set);
        $this->assertInstanceOf(Base::class, $set);
        $this->assertSame('stdClass', (string) $set->type());
        $this->assertSame(
            [$this->foo(), $this->baz()],
            $set->toPrimitive()
        );
    }

    public function testDiff()
    {
        $set = $this->set->diff(Base::of('stdClass', $this->bar()));

        $this->assertImmutability($set);
        $this->assertInstanceOf(Base::class, $set);
        $this->assertSame('stdClass', (string) $set->type());
        $this->assertSame(
            [$this->foo(), $this->baz()],
            $set->toPrimitive()
        );
    }

    public function testEquals()
    {
        $this->assertFalse($this->set->equals(new Base('stdClass')));
        $this->assertTrue($this->set->equals(
            Base::of('stdClass', $this->foo(), $this->baz(), $this->bar())
        ));
    }

    public function testFilter()
    {
        $set = $this->set->filter(static function(\stdClass $service): bool {
            static $i = 0;

            return $i++ % 2 === 0;
        });

        $this->assertImmutability($set);
        $this->assertInstanceOf(Base::class, $set);
        $this->assertSame('stdClass', (string) $set->type());
        $this->assertSame(
            [$this->foo(), $this->baz()],
            $set->toPrimitive()
        );
    }

    public function testForeach()
    {
        $set = $this->set->foreach(function($value): void {
            $this->assertInstanceOf('stdClass', $value);
        });

        $this->assertImmutability($set);
        $this->assertInstanceOf(Base::class, $set);
        $this->assertSame('stdClass', (string) $set->type());
        $this->assertCount(3, $set);
    }

    public function testGroupBy()
    {
        $map = $this->set->groupBy(static function(\stdClass $service): int {
            static $i = 0;

            return $i++ % 2;
        });

        $this->assertImmutability();
        $this->assertInstanceOf(MapInterface::class, $map);
        $this->assertSame('int', (string) $map->keyType());
        $this->assertSame(SetInterface::class, (string) $map->valueType());
        $this->assertSame('stdClass', (string) $map->get(0)->type());
        $this->assertSame('stdClass', (string) $map->get(1)->type());
        $this->assertSame(
            [$this->foo(), $this->baz()],
            $map->get(0)->toPrimitive()
        );
        $this->assertSame(
            [$this->bar()],
            $map->get(1)->toPrimitive()
        );
    }

    public function testMap()
    {
        $set = $this->set->map(static function(\stdClass $service) {
            return clone $service;
        });

        $this->assertImmutability($set);
        $this->assertInstanceOf(Base::class, $set);
        $this->assertSame('stdClass', (string) $set->type());
        $this->assertCount(3, $set);
    }

    public function testPartition()
    {
        $map = $this->set->partition(static function(\stdClass $service): bool {
            static $i = 0;

            return $i++ % 2 === 0;
        });

        $this->assertImmutability();
        $this->assertInstanceOf(MapInterface::class, $map);
        $this->assertSame('bool', (string) $map->keyType());
        $this->assertSame(SetInterface::class, (string) $map->valueType());
        $this->assertSame('stdClass', (string) $map->get(true)->type());
        $this->assertSame('stdClass', (string) $map->get(false)->type());
        $this->assertSame(
            [$this->foo(), $this->baz()],
            $map->get(true)->toPrimitive()
        );
        $this->assertSame(
            [$this->bar()],
            $map->get(false)->toPrimitive()
        );
    }

    public function testJoin()
    {
        $str = Set::of('string', 'foo', 'bar')->join(',');

        $this->assertInstanceOf(Str::class, $str);
        $this->assertSame('foo,bar', (string) $str);
    }

    public function testSort()
    {
        $stream = $this->set->sort(static function(\stdClass $service): int {
            return 1;
        });

        $this->assertImmutability();
        $this->assertInstanceOf(StreamInterface::class, $stream);
        $this->assertSame('stdClass', (string) $stream->type());
        $this->assertSame(
            [$this->bar(), $this->baz(), $this->foo()],
            $stream->toPrimitive()
        );
    }

    public function testMerge()
    {
        $set = $this->set->merge(Base::of('stdClass', $this->bar(), $expected = new \stdClass));

        $this->assertImmutability($set);
        $this->assertInstanceOf(Base::class, $set);
        $this->assertSame('stdClass', (string) $set->type());
        $this->assertSame(
            [$this->foo(), $this->bar(), $this->baz(), $expected],
            $set->toPrimitive()
        );
    }

    public function testReduce()
    {
        $value = $this->set->reduce(
            [],
            static function(array $values, \stdClass $value): array {
                $values[] = $value;

                return $values;
            }
        );

        $this->assertImmutability();
        $this->assertSame(
            [$this->foo(), $this->bar(), $this->baz()],
            $value
        );
    }

    public function testClear()
    {
        $set = $this->set->clear();

        $this->assertImmutability($set);
        $this->assertInstanceOf(Base::class, $set);
        $this->assertSame('stdClass', (string) $set->type());
        $this->assertCount(0, $set);
    }

    public function testMergeNonLoadedLazySets()
    {
        $set = Set::of(
            'stdClass',
            Lazy::service(
                new Name('foo'),
                $this->services
            ),
            Lazy::service(
                new Name('bar'),
                $this->services
            ),
            Lazy::service(
                new Name('baz'),
                $this->services
            )
        );
        $newSet = $this->set->merge($set);

        $this->assertCount(3, $newSet);
        $this->assertSame([$this->foo(), $this->bar(), $this->baz()], $newSet->toPrimitive());
    }

    private function assertImmutability(SetInterface $set = null): void
    {
        if ($set) {
            $this->assertNotSame($this->set, $set);
        }

        $this->assertInstanceOf(Set::class, $this->set);
        $this->assertSame('stdClass', (string) $this->set->type());
        $this->assertSame(
            [$this->foo(), $this->bar(), $this->baz()],
            $this->set->toPrimitive()
        );
    }

    private function foo()
    {
        return $this->services->build(new Name('foo'));
    }

    private function bar()
    {
        return $this->services->build(new Name('bar'));
    }

    private function baz()
    {
        return $this->services->build(new Name('baz'));
    }
}
