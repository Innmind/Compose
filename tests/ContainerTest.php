<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose;

use Innmind\Compose\{
    Container,
    Definitions,
    Arguments,
    Definition\Name,
    Definition\Argument,
    Definition\Argument\Type\Primitive,
    Definition\Service,
    Definition\Service\Constructor,
    Exception\NotFound
};
use Innmind\Immutable\Map;
use Psr\Container\ContainerInterface;
use PHPUnit\Framework\TestCase;
use Fixture\Innmind\Compose\ServiceFixture;

class ContainerTest extends TestCase
{
    private $container;

    public function setUp()
    {
        $definitions = new Definitions(
            new Arguments(
                new Argument(
                    new Name('firstArg'),
                    new Primitive('int')
                )
            ),
            (new Service(
                new Name('wished'),
                new Constructor(ServiceFixture::class),
                Service\Argument\Reference::fromValue('$firstArg'),
                Service\Argument\Reference::fromValue('$defaultStd')
            ))->exposeAs(new Name('foo')),
            new Service(
                new Name('defaultStd'),
                new Constructor('stdClass')
            )
        );

        $this->container = new Container(
            $definitions->inject(Map::of('string', 'mixed', ['firstArg'], [42]))
        );
    }

    public function testInterface()
    {
        $this->assertInstanceOf(ContainerInterface::class, $this->container);
    }

    public function testHas()
    {
        $this->assertTrue($this->container->has('foo'));
        $this->assertFalse($this->container->has('wished'));
        $this->assertFalse($this->container->has('defaultStd'));
        $this->assertFalse($this->container->has('unknown'));
    }

    public function testGet()
    {
        $service = $this->container->get('foo');

        $this->assertInstanceOf(ServiceFixture::class, $service);
        $this->assertSame($service, $this->container->get('foo'));
    }

    public function testThrowWhenGettingServiceViaInnerName()
    {
        $this->expectException(NotFound::class);
        $this->expectExceptionMessage('wished');

        $this->container->get('wished');
    }

    public function testThrowWhenGettingANonExposedService()
    {
        $this->expectException(NotFound::class);
        $this->expectExceptionMessage('defaultStd');

        $this->container->get('defaultStd');
    }

    public function testThrowWhenGettingUnknownService()
    {
        $this->expectException(NotFound::class);
        $this->expectExceptionMessage('unknown');

        $this->container->get('unknown');
    }
}
