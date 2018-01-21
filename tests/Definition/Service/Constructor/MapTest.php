<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose\Definition\Service\Constructor;

use Innmind\Compose\{
    Definition\Service\Constructor\Map,
    Definition\Service\Constructor,
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
}
