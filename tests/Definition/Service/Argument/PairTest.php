<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose\Definition\Service\Argument;

use Innmind\Compose\{
    Definition\Service\Argument\Pair,
    Definition\Service\Argument\Unwind,
    Definition\Service\Argument,
    Definition\Service\Arguments as Args,
    Definition\Service\Constructor,
    Definition\Service,
    Definition\Name,
    Definition\Argument as Arg,
    Services,
    Arguments,
    Dependencies,
    Exception\ValueNotSupported,
    Exception\LogicException,
    Compilation\Service\Argument\Pair as CompiledPair
};
use Innmind\Immutable\{
    StreamInterface,
    Stream,
    Str,
    Pair as ImmutablePair
};
use PHPUnit\Framework\TestCase;

class PairTest extends TestCase
{
    public function testFromValue()
    {
        $argument = Pair::fromValue('<foo,bar>', new Args);

        $this->assertInstanceOf(Pair::class, $argument);
        $this->assertInstanceOf(Argument::class, $argument);

        $argument = Pair::fromValue('<foo, bar>', new Args);

        $this->assertInstanceOf(Pair::class, $argument);
    }

    public function testThrowWhenNotAString()
    {
        $this->expectException(ValueNotSupported::class);

        Pair::fromValue(42, new Args);
    }

    public function testThrowWhenNotAPair()
    {
        $this->expectException(ValueNotSupported::class);
        $this->expectExceptionMessage('foo');

        Pair::fromValue('foo, bar', new Args);
    }

    public function testThrowWhenNotKeyIsUnwinding()
    {
        $this->expectException(LogicException::class);

        Pair::fromValue('<...$foo, bar>', new Args);
    }

    public function testThrowWhenNotValueIsUnwinding()
    {
        $this->expectException(LogicException::class);

        Pair::fromValue('<$foo, ...$bar>', new Args);
    }

    public function testResolveArgument()
    {
        $services = new Services(
            new Arguments,
            new Dependencies,
            (new Service(
                new Name('foo'),
                Constructor\Construct::fromString(Str::of('stdClass'))
            ))->exposeAs(new Name('foo'))
        );

        $value = Pair::fromValue('<foo, bar>', new Args)->resolve(
            Stream::of('mixed'),
            $services
        );

        $this->assertInstanceOf(StreamInterface::class, $value);
        $this->assertSame('mixed', (string) $value->type());
        $this->assertCount(1, $value);
        $this->assertInstanceOf(ImmutablePair::class, $value->current());
        $this->assertSame('foo', $value->current()->key());
        $this->assertSame('bar', $value->current()->value());
    }

    public function testCompile()
    {
        $this->assertInstanceOf(
            CompiledPair::class,
            Pair::fromValue('<foo,bar>', new Args)->compile()
        );
    }
}
