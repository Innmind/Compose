<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose\Definition\Service\Constructor;

use Innmind\Compose\{
    Definition\Service\Constructor\Set,
    Definition\Service\Constructor,
    Exception\ValueNotSupported
};
use Innmind\Immutable\{
    Str,
    Set as ImmutableSet
};
use PHPUnit\Framework\TestCase;

class SetTest extends TestCase
{
    public function testConstruct()
    {
        $construct = Set::fromString(Str::of('set<int>'));

        $this->assertInstanceOf(Constructor::class, $construct);

        $instance = $construct(1, 2);

        $this->assertInstanceOf(ImmutableSet::class, $instance);
        $this->assertSame('int', (string) $instance->type());
        $this->assertSame([1, 2], $instance->toPrimitive());
    }

    public function testThrowWhenNotOfExpectedFormat()
    {
        $this->expectException(ValueNotSupported::class);
        $this->expectExceptionMessage('foo');

        Set::fromString(Str::of('foo'));
    }
}
