<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose\Lazy;

use Innmind\Compose\{
    Lazy\Stream,
    Lazy,
    Definitions,
    Arguments,
    Definition\Name,
    Definition\Service,
    Definition\Service\Constructor\Construct
};
use Innmind\Immutable\{
    StreamInterface,
    Stream as Base,
    Str,
    MapInterface,
    Exception\OutOfBoundException,
    Exception\LogicException,
    Exception\InvalidArgumentException
};
use PHPUnit\Framework\TestCase;

class StreamTest extends TestCase
{
    private $stream;
    private $definitions;

    public function setUp()
    {
        $this->definitions = new Definitions(
            new Arguments,
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
        $this->stream = new Stream(
            'stdClass',
            new Lazy(
                new Name('foo'),
                $this->definitions
            ),
            new Lazy(
                new Name('bar'),
                $this->definitions
            ),
            new Lazy(
                new Name('baz'),
                $this->definitions
            )
        );
    }

    public function testInterface()
    {
        $this->assertInstanceOf(StreamInterface::class, $this->stream);
    }

    public function testType()
    {
        $this->assertInstanceOf(Str::class, $this->stream->type());
        $this->assertSame('stdClass', (string) $this->stream->type());
    }

    public function testSize()
    {
        $this->assertSame(3, $this->stream->size());
    }

    public function testCount()
    {
        $this->assertCount(3, $this->stream);
    }

    public function testToPrimitive()
    {
        $this->assertSame(
            [
                $this->foo(),
                $this->bar(),
                $this->baz(),
            ],
            $this->stream->toPrimitive()
        );
    }

    public function testIterator()
    {
        $foo = $this->foo();
        $bar = $this->bar();
        $baz = $this->baz();

        $this->assertSame(0, $this->stream->key());
        $this->assertSame($foo, $this->stream->current());
        $this->assertTrue($this->stream->valid());
        $this->assertNull($this->stream->next());
        $this->assertSame(1, $this->stream->key());
        $this->assertSame($bar, $this->stream->current());
        $this->assertTrue($this->stream->valid());
        $this->stream->next();
        $this->assertSame(2, $this->stream->key());
        $this->assertSame($baz, $this->stream->current());
        $this->assertTrue($this->stream->valid());
        $this->stream->next();
        $this->assertFalse($this->stream->valid());
        $this->assertNull($this->stream->rewind());
        $this->assertSame(0, $this->stream->key());
    }

    public function testArrayAccess()
    {
        $this->assertTrue(isset($this->stream[0]));
        $this->assertFalse(isset($this->stream[3]));
        $this->assertSame(
            $this->foo(),
            $this->stream[0]
        );

        $this->expectException(OutOfBoundException::class);

        $this->stream[3];
    }

    public function testThrowWhenTryingToSet()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('You can\'t modify a stream');

        $this->stream[3] = new \stdClass;
    }

    public function testThrowWhenTryingToUnset()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('You can\'t modify a stream');

        unset($this->stream[0]);
    }

    public function testGet()
    {
        $this->assertSame(
            $this->foo(),
            $this->stream->get(0)
        );
        $this->assertSame(
            $this->bar(),
            $this->stream->get(1)
        );
        $this->assertSame(
            $this->baz(),
            $this->stream->get(2)
        );
    }

    public function testThrowWhenTryingToGetUnknownIndex()
    {
        $this->expectException(OutOfBoundException::class);

        $this->stream->get(3);
    }

    public function testDiff()
    {
        $stream = Base::of('stdClass', $this->bar());

        $diff = $this->stream->diff($stream);

        $this->assertImmutability($diff);
        $this->assertInstanceOf(Base::class, $diff);
        $this->assertSame('stdClass', (string) $diff->type());
        $this->assertSame(
            [
                $this->foo(),
                $this->baz(),
            ],
            $diff->toPrimitive()
        );
    }

    public function testDistinct()
    {
        $distinct = $this->stream->distinct();

        $this->assertImmutability($distinct);
        $this->assertInstanceOf(Base::class, $distinct);
        $this->assertSame('stdClass', (string) $distinct->type());
        $this->assertCount(3, $distinct);
    }

    public function testDrop()
    {
        $drop = $this->stream->drop(1);

        $this->assertImmutability($drop);
        $this->assertInstanceOf(Stream::class, $drop);
        $this->assertSame('stdClass', (string) $drop->type());
        $this->assertSame(
            [
                $this->bar(),
                $this->baz(),
            ],
            $drop->toPrimitive()
        );
    }

    public function testDropEnd()
    {
        $drop = $this->stream->dropEnd(1);

        $this->assertImmutability($drop);
        $this->assertInstanceOf(Stream::class, $drop);
        $this->assertSame('stdClass', (string) $drop->type());
        $this->assertSame(
            [
                $this->foo(),
                $this->bar(),
            ],
            $drop->toPrimitive()
        );
    }

    public function testEquals()
    {
        $this->assertFalse($this->stream->equals(new Base('stdClass')));
        $this->assertTrue($this->stream->equals(Base::of(
            'stdClass',
            $this->foo(),
            $this->bar(),
            $this->baz()
        )));
    }

    public function testFilter()
    {
        $stream = $this->stream->filter(static function(\stdClass $service): bool {
            static $i = 0;

            return $i++ % 2 === 0;
        });

        $this->assertImmutability($stream);
        $this->assertInstanceOf(Base::class, $stream);
        $this->assertSame('stdClass', (string) $stream->type());
        $this->assertSame(
            [
                $this->foo(),
                $this->baz(),
            ],
            $stream->toPrimitive()
        );
    }

    public function testForeach()
    {
        $this->stream->foreach(function($value): void {
            $this->assertInstanceOf('stdClass', $value);
        });
        $this->assertImmutability();
    }

    public function testGroupBy()
    {
        $map = $this->stream->groupBy(static function(): int {
            static $i = 0;

            return $i++ % 2;
        });

        $this->assertImmutability();
        $this->assertInstanceOf(MapInterface::class, $map);
        $this->assertSame('int', (string) $map->keyType());
        $this->assertSame(StreamInterface::class, (string) $map->valueType());
        $this->assertSame('stdClass', (string) $map->get(0)->type());
        $this->assertSame('stdClass', (string) $map->get(1)->type());
        $this->assertSame(
            [
                $this->foo(),
                $this->baz(),
            ],
            $map->get(0)->toPrimitive()
        );
        $this->assertSame(
            [
                $this->bar(),
            ],
            $map->get(1)->toPrimitive()
        );
    }

    public function testFirst()
    {
        $this->assertSame(
            $this->foo(),
            $this->stream->first()
        );
    }

    public function testLast()
    {
        $this->assertSame(
            $this->baz(),
            $this->stream->last()
        );
    }

    public function testContains()
    {
        $this->assertFalse($this->stream->contains(new \stdClass));
        $this->assertTrue($this->stream->contains(
            $this->foo()
        ));
    }

    public function testIndexOf()
    {
        $this->assertSame(
            1,
            $this->stream->indexOf(
                $this->bar()
            )
        );
    }

    public function testIndices()
    {
        $indices = $this->stream->indices();

        $this->assertImmutability($indices);
        $this->assertInstanceOf(Base::class, $indices);
        $this->assertSame('int', (string) $indices->type());
        $this->assertSame([0, 1, 2], $indices->toPrimitive());
    }

    public function testMap()
    {
        $stream = $this->stream->map(static function($service) {
            return clone $service;
        });

        $this->assertImmutability();
        $this->assertInstanceOf(Base::class, $stream);
        $this->assertSame('stdClass', (string) $stream->type());
        $this->assertCount(3, $stream);
        $this->assertFalse($this->stream->equals($stream));
    }

    public function testPad()
    {
        $stream = $this->stream->pad(4, $expected = new \stdClass);

        $this->assertImmutability($stream);
        $this->assertInstanceOf(Stream::class, $stream);
        $this->assertSame('stdClass', (string) $stream->type());
        $this->assertSame(
            [
                $this->foo(),
                $this->bar(),
                $this->baz(),
                $expected,
            ],
            $stream->toPrimitive()
        );
    }

    public function testThrowWhenPadElementNotValid()
    {
        $this->expectException(InvalidArgumentException::class);

        $this->stream->pad(4, 42);
    }

    public function testPartition()
    {
        $partitions = $this->stream->partition(static function(): bool {
            static $i = 0;

            return $i++ % 2 === 0;
        });

        $this->assertImmutability();
        $this->assertInstanceOf(MapInterface::class, $partitions);
        $this->assertSame('bool', (string) $partitions->keyType());
        $this->assertSame(StreamInterface::class, (string) $partitions->valueType());
        $this->assertSame('stdClass', (string) $partitions->get(true)->type());
        $this->assertSame('stdClass', (string) $partitions->get(false)->type());
        $this->assertSame(
            [
                $this->foo(),
                $this->baz(),
            ],
            $partitions->get(true)->toPrimitive()
        );
        $this->assertSame(
            [
                $this->bar(),
            ],
            $partitions->get(false)->toPrimitive()
        );
    }

    public function testSlice()
    {
        $stream = $this->stream->slice(1, 2);

        $this->assertImmutability($stream);
        $this->assertInstanceOf(Stream::class, $stream);
        $this->assertSame('stdClass', (string) $stream->type());
        $this->assertSame(
            [
                $this->bar(),
            ],
            $stream->toPrimitive()
        );
    }

    public function testSplitAt()
    {
        $streams = $this->stream->splitAt(1);

        $this->assertImmutability($streams);
        $this->assertInstanceOf(Base::class, $streams);
        $this->assertSame(StreamInterface::class, (string) $streams->type());
        $this->assertInstanceOf(Stream::class, $streams->get(0));
        $this->assertInstanceOf(Stream::class, $streams->get(1));
        $this->assertSame('stdClass', (string) $streams->get(0)->type());
        $this->assertSame('stdClass', (string) $streams->get(1)->type());
        $this->assertSame(
            [$this->foo()],
            $streams->get(0)->toPrimitive()
        );
        $this->assertSame(
            [
                $this->bar(),
                $this->baz(),
            ],
            $streams->get(1)->toPrimitive()
        );
    }

    public function testTake()
    {
        $stream = $this->stream->take(1);

        $this->assertImmutability($stream);
        $this->assertInstanceOf(Stream::class, $stream);
        $this->assertSame('stdClass', (string) $stream->type());
        $this->assertSame(
            [$this->foo()],
            $stream->toPrimitive()
        );
    }

    public function testTakeEnd()
    {
        $stream = $this->stream->takeEnd(1);

        $this->assertImmutability($stream);
        $this->assertInstanceOf(Stream::class, $stream);
        $this->assertSame('stdClass', (string) $stream->type());
        $this->assertSame(
            [$this->baz()],
            $stream->toPrimitive()
        );
    }

    public function testAppend()
    {
        $stream = $this->stream->append(Base::of('stdClass', $expected = new \stdClass));

        $this->assertImmutability($stream);
        $this->assertInstanceOf(Base::class, $stream);
        $this->assertSame('stdClass', (string) $stream->type());
        $this->assertSame(
            [
                $this->foo(),
                $this->bar(),
                $this->baz(),
                $expected,
            ],
            $stream->toPrimitive()
        );
    }

    public function testIntersect()
    {
        $stream = $this->stream->intersect(Base::of('stdClass', $this->bar()));

        $this->assertImmutability($stream);
        $this->assertInstanceOf(Base::class, $stream);
        $this->assertSame('stdClass', (string) $stream->type());
        $this->assertSame([$this->bar()], $stream->toPrimitive());
    }

    public function testJoin()
    {
        $str = (new Stream('string', 'foo', 'bar'))->join(',');

        $this->assertInstanceOf(Str::class, $str);
        $this->assertSame('foo,bar', (string) $str);
    }

    public function testAdd()
    {
        $stream = $this->stream->add($expected = new \stdClass);

        $this->assertImmutability($stream);
        $this->assertInstanceOf(Stream::class, $stream);
        $this->assertSame('stdClass', (string) $stream->type());
        $this->assertSame(
            [
                $this->foo(),
                $this->bar(),
                $this->baz(),
                $expected,
            ],
            $stream->toPrimitive()
        );
    }

    public function testSort()
    {
        $stream = $this->stream->sort(static function(): int {
            return 1;
        });

        $this->assertImmutability($stream);
        $this->assertInstanceOf(Base::class, $stream);
        $this->assertSame('stdClass', (string) $stream->type());
        $this->assertSame(
            [
                $this->bar(),
                $this->baz(),
                $this->foo(),
            ],
            $stream->toPrimitive()
        );
    }

    public function testReduce()
    {
        $value = $this->stream->reduce(
            [],
            static function(array $values, \stdClass $service): array {
                $values[] = $service;

                return $values;
            }
        );

        $this->assertImmutability();
        $this->assertSame([$this->foo(), $this->bar(), $this->baz()], $value);
    }

    public function testClear()
    {
        $stream = $this->stream->clear();

        $this->assertImmutability($stream);
        $this->assertInstanceOf(Base::class, $stream);
        $this->assertSame('stdClass', (string) $stream->type());
        $this->assertCount(0, $stream);
    }

    public function testReverse()
    {
        $stream = $this->stream->reverse();

        $this->assertImmutability($stream);
        $this->assertInstanceOf(Stream::class, $stream);
        $this->assertSame('stdClass', (string) $stream->type());
        $this->assertSame(
            [$this->baz(), $this->bar(), $this->foo()],
            $stream->toPrimitive()
        );
    }

    private function assertImmutability(StreamInterface $stream = null): void
    {
        if ($stream) {
            $this->assertNotSame($this->stream, $stream);
        }

        $this->assertInstanceOf(Stream::class, $this->stream);
        $this->assertSame('stdClass', (string) $this->stream->type());
        $this->assertSame(
            [$this->foo(), $this->bar(), $this->baz()],
            $this->stream->toPrimitive()
        );
    }

    private function foo()
    {
        return $this->definitions->build(new Name('foo'));
    }

    private function bar()
    {
        return $this->definitions->build(new Name('bar'));
    }

    private function baz()
    {
        return $this->definitions->build(new Name('baz'));
    }
}
