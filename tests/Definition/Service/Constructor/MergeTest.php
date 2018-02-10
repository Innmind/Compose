<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose\Definition\Service\Constructor;

use Innmind\Compose\{
    Definition\Service\Constructor\Merge,
    Definition\Service\Constructor,
    Exception\ValueNotSupported,
    Compilation\Service\Constructor\Merge as CompiledMerge,
    Compilation\Service\Argument as CompiledArgument,
};
use Innmind\Immutable\{
    Str,
    Map,
    Set,
    Exception\InvalidArgumentException
};
use PHPUnit\Framework\TestCase;

class MergeTest extends TestCase
{
    public function testMergeSet()
    {
        $merge = Merge::fromString(Str::of('merge'));

        $this->assertInstanceOf(Constructor::class, $merge);
        $this->assertSame('merge', (string) $merge);

        $structure = $merge(
            Set::of('int', 1),
            Set::of('int', 1, 2)
        );

        $this->assertInstanceOf(Set::class, $structure);
        $this->assertSame('int', (string) $structure->type());
        $this->assertCount(2, $structure);
        $this->assertSame([1, 2], $structure->toPrimitive());
    }

    public function testMergeMap()
    {
        $merge = Merge::fromString(Str::of('merge'));

        $this->assertInstanceOf(Constructor::class, $merge);

        $structure = $merge(
            Map::of('int', 'int', [1], [1]),
            Map::of('int', 'int', [1, 2], [2, 1])
        );

        $this->assertInstanceOf(Map::class, $structure);
        $this->assertSame('int', (string) $structure->keyType());
        $this->assertSame('int', (string) $structure->valueType());
        $this->assertCount(2, $structure);
        $this->assertSame(2, $structure->get(1));
        $this->assertSame(1, $structure->get(2));
    }

    public function testThrowWhenMergingUnsupportedStructures()
    {
        $merge = Merge::fromString(Str::of('merge'));

        $this->expectException(InvalidArgumentException::class);

        $merge(1, 2);
    }

    public function testThrowWhenNotOfExpectedFormat()
    {
        $this->expectException(ValueNotSupported::class);
        $this->expectExceptionMessage('foo');

        Merge::fromString(Str::of('foo'));
    }

    public function testCompile()
    {
        $this->assertInstanceOf(
            CompiledMerge::class,
            Merge::fromString(Str::of('merge'))->compile(
                $this->createMock(CompiledArgument::class),
                $this->createMock(CompiledArgument::class)
            )
        );
    }
}
