<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose\Definition\Service\Constructor;

use Innmind\Compose\{
    Definition\Service\Constructor\Set,
    Definition\Service\Constructor\Construct,
    Definition\Service\Constructor,
    Definition\Service,
    Definition\Name,
    Services,
    Arguments,
    Dependencies,
    Lazy,
    Lazy\Set as LazySet,
    Exception\ValueNotSupported
};
use Innmind\Immutable\Str;
use PHPUnit\Framework\TestCase;

class SetTest extends TestCase
{
    public function testConstruct()
    {
        $construct = Set::fromString(Str::of('set<int>'));

        $this->assertInstanceOf(Constructor::class, $construct);
        $this->assertSame('set<int>', (string) $construct);

        $instance = $construct(1, 2);

        $this->assertInstanceOf(LazySet::class, $instance);
        $this->assertSame('int', (string) $instance->type());
        $this->assertCount(2, $instance);
        $this->assertSame([1, 2], $instance->toPrimitive());
    }

    public function testThrowWhenNotOfExpectedFormat()
    {
        $this->expectException(ValueNotSupported::class);
        $this->expectExceptionMessage('foo');

        Set::fromString(Str::of('foo'));
    }

    public function testLoadLazyService()
    {
        $construct = Set::fromString(Str::of('set<stdClass>'));

        $instance = $construct(
            new Lazy(
                new Name('foo'),
                new Services(
                    new Arguments,
                    new Dependencies,
                    new Service(
                        new Name('foo'),
                        Construct::fromString(Str::of('stdClass'))
                    )
                )
            )
        );

        $this->assertInstanceOf(LazySet::class, $instance);
        $this->assertCount(1, $instance);
        $this->assertInstanceOf('stdClass', $instance->current());
    }
}
