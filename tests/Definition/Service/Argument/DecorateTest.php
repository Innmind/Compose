<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose\Definition\Service\Argument;

use Innmind\Compose\{
    Definition\Service\Argument\Decorate,
    Definition\Service\Argument,
    Definition\Service\Arguments as Args,
    Definition\Service\Constructor,
    Definition\Service,
    Definition\Name,
    Services,
    Arguments,
    Dependencies,
    Exception\ValueNotSupported,
    Exception\DecoratedArgumentCannotBeResolved,
    Exception\DecoratedArgumentCannotBeCompiled
};
use Innmind\Immutable\{
    Stream,
    Str
};
use PHPUnit\Framework\TestCase;

class DecorateTest extends TestCase
{
    public function testFromValue()
    {
        $argument = Decorate::fromValue('@decorated', new Args);

        $this->assertInstanceOf(Decorate::class, $argument);
        $this->assertInstanceOf(Argument::class, $argument);
    }

    public function testThrowWhenInvalidValue()
    {
        $this->expectException(ValueNotSupported::class);

        Decorate::fromValue(42, new Args);
    }

    public function testThrowWhenTryingToResolve()
    {
        $this->expectException(DecoratedArgumentCannotBeResolved::class);

        Decorate::fromValue('@decorated', new Args)->resolve(
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
    }

    public function testCompile()
    {
        $this->expectException(DecoratedArgumentCannotBeCompiled::class);

        Decorate::fromValue('@decorated', new Args)->compile();
    }
}
