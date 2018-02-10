<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose\Definition\Service\Argument;

use Innmind\Compose\{
    Definition\Service\Argument\Primitive,
    Definition\Service\Argument,
    Definition\Service\Arguments as Args,
    Definition\Service\Constructor,
    Definition\Service,
    Definition\Name,
    Services,
    Arguments,
    Dependencies,
    Compilation\Service\Argument\Primitive as CompiledPrimitive
};
use Innmind\Immutable\{
    StreamInterface,
    Stream,
    Str
};
use PHPUnit\Framework\TestCase;

class PrimitiveTest extends TestCase
{
    public function testFromValue()
    {
        $argument = Primitive::fromValue(42, new Args);

        $this->assertInstanceOf(Primitive::class, $argument);
        $this->assertInstanceOf(Argument::class, $argument);
    }

    public function testResolve()
    {
        $value = Primitive::fromValue(42, new Args)->resolve(
            Stream::of('mixed'),
            new Services(
                new Arguments,
                new Dependencies,
                (new Service(
                    new Name('foo'),
                    Constructor\Construct::fromString(Str::of('stdClass'))
                ))->exposeAs(new Name('foo'))
            )
        );

        $this->assertInstanceOf(StreamInterface::class, $value);
        $this->assertSame('mixed', (string) $value->type());
        $this->assertCount(1, $value);
        $this->assertSame(42, $value->current());
    }

    public function testCompile()
    {
        $this->assertInstanceOf(
            CompiledPrimitive::class,
            Primitive::fromValue(42, new Args)->compile()
        );
    }
}
