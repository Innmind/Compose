<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose\Definition\Service\Argument;

use Innmind\Compose\{
    Definition\Service\Argument\Tunnel,
    Definition\Service\Argument\Primitive,
    Definition\Service\Argument\Decorate,
    Definition\Service\Argument,
    Definition\Service\Arguments,
    Definition\Service\Constructor,
    Definition\Service,
    Definition\Name,
    Definition\Dependency,
    Services,
    Arguments as Args,
    Dependencies,
    Exception\LogicException
};
use Innmind\Immutable\{
    StreamInterface,
    Stream
};
use PHPUnit\Framework\TestCase;

class TunnelTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Argument::class,
            new Tunnel(
                new Name('foo'),
                $this->createMock(Argument::class)
            )
        );
    }

    public function testThrowWhenTryingToBuildFromValue()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Can\'t be used outside Service::tunnel()');

        Tunnel::fromValue('foo', new Arguments);
    }

    public function testResolve()
    {
        $services = new Services(
            new Args,
            new Dependencies(
                new Dependency(
                    new Name('dep'),
                    new Services(
                        new Args,
                        new Dependencies,
                        (new Service(
                            new Name('foo'),
                            $this->createMock(Constructor::class),
                            $argument = new Primitive(42),
                            new Decorate
                        ))->exposeAs(new Name('bar'))
                    )
                )
            )
        );
        $tunnel = new Tunnel(new Name('dep.bar'), $argument);

        $arguments = $tunnel->resolve(new Stream('mixed'), $services);

        $this->assertInstanceOf(StreamInterface::class, $arguments);
        $this->assertSame('mixed', (string) $arguments->type());
        $this->assertCount(1, $arguments);
        $this->assertSame(42, $arguments->first());
    }
}
