<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose\Definition\Service\Argument;

use Innmind\Compose\{
    Definition\Service\Argument\Primitive,
    Definition\Service\Argument,
    Definition\Service\Constructor,
    Definition\Service,
    Definition\Name,
    Definitions,
    Arguments
};
use Innmind\Immutable\{
    StreamInterface,
    Stream
};
use PHPUnit\Framework\TestCase;

class PrimitiveTest extends TestCase
{
    public function testFromValue()
    {
        $argument = Primitive::fromValue(42);

        $this->assertInstanceOf(Primitive::class, $argument);
        $this->assertInstanceOf(Argument::class, $argument);
    }

    public function testResolve()
    {
        $value = Primitive::fromValue(42)->resolve(
            Stream::of('mixed'),
            new Definitions(
                new Arguments,
                (new Service(
                    new Name('foo'),
                    new Constructor('stdClass')
                ))->exposeAs(new Name('foo'))
            )
        );

        $this->assertInstanceOf(StreamInterface::class, $value);
        $this->assertSame('mixed', (string) $value->type());
        $this->assertCount(1, $value);
        $this->assertSame(42, $value->current());
    }
}
