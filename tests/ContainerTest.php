<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose;

use Innmind\Compose\{
    Container,
    Services,
    Arguments,
    Definition\Name,
    Definition\Argument,
    Definition\Argument\Type\Primitive,
    Definition\Service,
    Definition\Service\Constructor,
    Definition\Service\Arguments as Args,
    Exception\NotFound
};
use Innmind\Immutable\{
    Map,
    Str
};
use Psr\Container\ContainerInterface;
use PHPUnit\Framework\TestCase;
use Fixture\Innmind\Compose\ServiceFixture;

class ContainerTest extends TestCase
{
    private $container;

    public function setUp()
    {
        $services = new Services(
            new Arguments(
                new Argument(
                    new Name('firstArg'),
                    new Primitive('int')
                )
            ),
            (new Service(
                new Name('wished'),
                Constructor\Construct::fromString(Str::of(ServiceFixture::class)),
                Service\Argument\Reference::fromValue('$firstArg', new Args),
                Service\Argument\Reference::fromValue('$defaultStd', new Args)
            ))->exposeAs(new Name('foo')),
            new Service(
                new Name('defaultStd'),
                Constructor\Construct::fromString(Str::of('stdClass'))
            )
        );

        $this->container = new Container(
            $services->inject(Map::of('string', 'mixed', ['firstArg'], [42]))
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
