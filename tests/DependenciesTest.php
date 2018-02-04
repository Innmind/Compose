<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose;

use Innmind\Compose\{
    Dependencies,
    Definition\Dependency,
    Definition\Dependency\Parameter,
    Definition\Name,
    Definition\Service,
    Definition\Service\Constructor\Construct,
    Definition\Service\Constructor,
    Definition\Service\Argument\Primitive,
    Definition\Service\Argument\Reference,
    Definition\Service\Argument\Decorate,
    Definition\Service\Argument\Tunnel,
    Definition\Argument,
    Definition\Argument\Type\Instance,
    Services,
    Arguments,
    Lazy,
    Exception\ReferenceNotFound,
    Exception\ArgumentNotProvided,
    Exception\CircularDependency,
    Exception\LogicException
};
use Innmind\Immutable\{
    Str,
    StreamInterface,
    Stream,
    MapInterface
};
use PHPUnit\Framework\TestCase;
use Fixture\Innmind\Compose\ServiceFixture;

class DependenciesTest extends TestCase
{
    public function testBuild()
    {
        $dependencies = new Dependencies(
            $first = new Dependency(
                new Name('first'),
                new Services(
                    new Arguments,
                    new Dependencies,
                    (new Service(
                        new Name('foo'),
                        Construct::fromString(Str::of('stdClass'))
                    ))->exposeAs(new Name('bar'))
                )
            ),
            $second = new Dependency(
                new Name('second'),
                new Services(
                    new Arguments,
                    new Dependencies,
                    (new Service(
                        new Name('foo'),
                        Construct::fromString(Str::of('stdClass'))
                    ))->exposeAs(new Name('bar'))
                )
            )
        );

        $this->assertSame(
            $first->build(new Name('bar')),
            $dependencies->build(new Name('first.bar'))
        );
        $this->assertSame(
            $second->build(new Name('bar')),
            $dependencies->build(new Name('second.bar'))
        );
    }

    public function testThrowWhenNameNotNamespaced()
    {
        $dependencies = new Dependencies(
            new Dependency(
                new Name('first'),
                new Services(
                    new Arguments,
                    new Dependencies,
                    (new Service(
                        new Name('foo'),
                        Construct::fromString(Str::of('stdClass'))
                    ))->exposeAs(new Name('bar'))
                )
            )
        );

        $this->expectException(ReferenceNotFound::class);
        $this->expectExceptionMessage('bar');

        $dependencies->build(new Name('bar'));
    }

    public function testThrowWhenNamespaceNotFound()
    {
        $dependencies = new Dependencies(
            new Dependency(
                new Name('first'),
                new Services(
                    new Arguments,
                    new Dependencies,
                    (new Service(
                        new Name('foo'),
                        Construct::fromString(Str::of('stdClass'))
                    ))->exposeAs(new Name('bar'))
                )
            )
        );

        $this->expectException(ReferenceNotFound::class);
        $this->expectExceptionMessage('second.bar');

        $dependencies->build(new Name('second.bar'));
    }

    public function testThrowWhenInnerServiceNotFound()
    {
        $dependencies = new Dependencies(
            new Dependency(
                new Name('first'),
                new Services(
                    new Arguments,
                    new Dependencies,
                    (new Service(
                        new Name('foo'),
                        Construct::fromString(Str::of('stdClass'))
                    ))->exposeAs(new Name('bar'))
                )
            )
        );

        $this->expectException(ReferenceNotFound::class);
        $this->expectExceptionMessage('first.foo');

        $dependencies->build(new Name('first.foo'));
    }

    public function testBind()
    {
        $dependencies = new Dependencies(
            new Dependency(
                new Name('first'),
                new Services(
                    new Arguments(
                        new Argument(
                            new Name('arg'),
                            new Instance('stdClass')
                        )
                    ),
                    new Dependencies,
                    (new Service(
                        new Name('foo'),
                        Construct::fromString(Str::of(ServiceFixture::class)),
                        new Primitive(42),
                        new Reference(new Name('arg'))
                    ))->exposeAs(new Name('bar'))
                ),
                Parameter::fromValue(
                    new Name('arg'),
                    '$std'
                )
            )
        );
        $upper = new Services(
            new Arguments,
            $dependencies,
            new Service(
                new Name('std'),
                Construct::fromString(Str::of('stdClass'))
            ),
            (new Service(
                new Name('foo'),
                Construct::fromString(Str::of(ServiceFixture::class)),
                new Primitive(24),
                new Reference(new Name('std')),
                new Reference(new Name('first.bar'))
            ))->exposeAs(new Name('bar'))
        );

        $services = $dependencies->bind($upper);

        $this->assertInstanceOf(Services::class, $services);
        $this->assertNotSame($services, $upper);

        $service = $services->build(new Name('bar'));

        $this->assertInstanceOf(ServiceFixture::class, $service);

        //assert the original object is not aware of the binding
        $this->expectException(ArgumentNotProvided::class);
        $this->expectExceptionMessage('arg');

        $dependencies->build(new Name('first.bar'));
    }

    public function testBindDependenciesInTheRightOrderWhenCrossDependenciesDependency()
    {
        $dependencies = new Dependencies(
            new Dependency(
                new Name('first'),
                new Services(
                    new Arguments(
                        new Argument(
                            new Name('arg'),
                            new Instance('stdClass')
                        )
                    ),
                    new Dependencies,
                    (new Service(
                        new Name('foo'),
                        Construct::fromString(Str::of(ServiceFixture::class)),
                        new Primitive(24),
                        new Reference(new Name('arg'))
                    ))->exposeAs(new Name('bar'))
                ),
                Parameter::fromValue(new Name('arg'), '$third.bar')
            ),
            new Dependency(
                new Name('second'),
                new Services(
                    new Arguments(
                        new Argument(
                            new Name('foo'),
                            new Instance(ServiceFixture::class)
                        ),
                        new Argument(
                            new Name('bar'),
                            new Instance('stdClass')
                        )
                    ),
                    new Dependencies,
                    (new Service(
                        new Name('watev'),
                        Construct::fromString(Str::of(ServiceFixture::class)),
                        new Primitive(42),
                        new Reference(new Name('bar')),
                        new Reference(new Name('foo'))
                    ))->exposeAs(new Name('bar'))
                ),
                Parameter::fromValue(new Name('foo'), '$first.bar'),
                Parameter::fromValue(new Name('bar'), '$third.bar')
            ),
            new Dependency(
                new Name('third'),
                new Services(
                    new Arguments,
                    new Dependencies,
                    (new Service(
                        new Name('foo'),
                        Construct::fromString(Str::of('stdClass'))
                    ))->exposeAs(new Name('bar'))
                )
            )
        );
        $upper = new Services(
            new Arguments,
            $dependencies
        );

        $services = $dependencies->bind($upper);

        $this->assertInstanceOf(Services::class, $services);
        $this->assertNotSame($dependencies, $services->dependencies());
        $deps = $services->dependencies();
        $this->assertSame(
            $deps->build(new Name('third.bar')),
            $deps->build(new Name('first.bar'))->second
        );
        $this->assertSame(
            $deps->build(new Name('third.bar')),
            $deps->build(new Name('second.bar'))->second
        );
        $this->assertSame(
            $deps->build(new Name('first.bar')),
            $deps->build(new Name('second.bar'))->third[0]
        );
    }

    public function testLazy()
    {
        $dependencies = new Dependencies(
            new Dependency(
                new Name('first'),
                new Services(
                    new Arguments,
                    new Dependencies,
                    (new Service(
                        new Name('foo'),
                        Construct::fromString(Str::of('stdClass'))
                    ))->exposeAs(new Name('bar'))
                )
            ),
            new Dependency(
                new Name('second'),
                new Services(
                    new Arguments,
                    new Dependencies,
                    (new Service(
                        new Name('foo'),
                        Construct::fromString(Str::of('stdClass'))
                    ))->exposeAs(new Name('bar'))
                )
            )
        );

        $this->assertInstanceOf(Lazy::class, $dependencies->lazy(new Name('first.bar')));
        $this->assertInstanceOf(Lazy::class, $dependencies->lazy(new Name('second.bar')));
        $this->assertSame(
            $dependencies->build(new Name('first.bar')),
            $dependencies->lazy(new Name('first.bar'))->load()
        );
        $this->assertSame(
            $dependencies->build(new Name('second.bar')),
            $dependencies->lazy(new Name('second.bar'))->load()
        );
    }

    public function testThrowWhenTryingToLazyLoadNonNamespacedName()
    {
        $dependencies = new Dependencies(
            new Dependency(
                new Name('first'),
                new Services(
                    new Arguments,
                    new Dependencies,
                    (new Service(
                        new Name('foo'),
                        Construct::fromString(Str::of('stdClass'))
                    ))->exposeAs(new Name('bar'))
                )
            )
        );

        $this->expectException(ReferenceNotFound::class);
        $this->expectExceptionMessage('first');

        $dependencies->lazy(new Name('first'));
    }

    public function testThrowWhenTryingToLazyLoadServiceWithItsInnerName()
    {
        $dependencies = new Dependencies(
            new Dependency(
                new Name('first'),
                new Services(
                    new Arguments,
                    new Dependencies,
                    (new Service(
                        new Name('foo'),
                        Construct::fromString(Str::of('stdClass'))
                    ))->exposeAs(new Name('bar'))
                )
            )
        );

        $this->expectException(ReferenceNotFound::class);
        $this->expectExceptionMessage('first.foo');

        $dependencies->lazy(new Name('first.foo'));
    }

    public function testThrowWhenTryingToLazyLoadUnknownDependency()
    {
        $dependencies = new Dependencies(
            new Dependency(
                new Name('first'),
                new Services(
                    new Arguments,
                    new Dependencies,
                    (new Service(
                        new Name('foo'),
                        Construct::fromString(Str::of('stdClass'))
                    ))->exposeAs(new Name('bar'))
                )
            )
        );

        $this->expectException(ReferenceNotFound::class);
        $this->expectExceptionMessage('second.bar');

        $dependencies->lazy(new Name('second.bar'));
    }

    public function testThrowWhenTryingToLazyLoadNonExposedService()
    {
        $dependencies = new Dependencies(
            new Dependency(
                new Name('first'),
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

        $this->expectException(ReferenceNotFound::class);
        $this->expectExceptionMessage('first.foo');

        $dependencies->lazy(new Name('first.foo'));
    }

    public function testThrowWhenCircularDependencyFound()
    {
        $this->expectException(CircularDependency::class);
        $this->expectExceptionMessage('foo -> baz -> foo');

        new Dependencies(
            new Dependency(
                new Name('foo'),
                new Services(
                    new Arguments,
                    new Dependencies,
                    (new Service(
                        new Name('foo'),
                        Construct::fromString(Str::of('stdClass'))
                    ))->exposeAs(new Name('bar'))
                ),
                Parameter::fromValue(new Name('watev'), '$baz.bar')
            ),
            new Dependency(
                new Name('bar'),
                new Services(
                    new Arguments,
                    new Dependencies
                )
            ),
            new Dependency(
                new Name('baz'),
                new Services(
                    new Arguments,
                    new Dependencies,
                    (new Service(
                        new Name('foo'),
                        Construct::fromString(Str::of('stdClass'))
                    ))->exposeAs(new Name('bar'))
                ),
                Parameter::fromValue(new Name('watev'), '$foo.bar')
            )
        );
    }

    public function testFeed()
    {
        $dependencies = new Dependencies(
            new Dependency(
                new Name('first'),
                new Services(
                    new Arguments(
                        new Argument(
                            new Name('std'),
                            new Instance('stdClass')
                        )
                    ),
                    new Dependencies,
                    (new Service(
                        new Name('foo'),
                        Construct::fromString(Str::of(ServiceFixture::class)),
                        new Primitive(24),
                        new Reference(new Name('std'))
                    ))->exposeAs(new Name('bar'))
                ),
                Parameter::fromValue(new Name('std'), '$foo')
            )
        );
        $services = new Services(
            new Arguments,
            new Dependencies,
            (new Service(
                new Name('foo'),
                Construct::fromString(Str::of('stdClass'))
            ))->exposeAs(new Name('bar'))
        );

        $dependencies2 = $dependencies->feed(new Name('first'), $services);

        $this->assertInstanceOf(Dependencies::class, $dependencies2);
        $this->assertNotSame($dependencies2, $dependencies);
        $service = $dependencies2->build(new Name('first.bar'));
        $this->assertInstanceOf(ServiceFixture::class, $service);
        $this->assertSame(24, $service->first);
        $this->assertInstanceOf('stdClass', $service->second);
        $this->assertSame(
            $services->build(new Name('bar')),
            $service->second
        );

        $this->expectException(ArgumentNotProvided::class);

        $dependencies->build(new Name('first.bar'));
    }

    public function testDecorate()
    {
        $dependencies = new Dependencies(
            new Dependency(
                new Name('dep'),
                new Services(
                    new Arguments,
                    new Dependencies,
                    (new Service(
                        new Name('foo'),
                        $this->createMock(Constructor::class),
                        new Decorate,
                        new Primitive(24)
                    ))->exposeAs(new Name('bar'))
                )
            )
        );

        $service = $dependencies->decorate(
            new Name('dep.bar'),
            new Name('decorated')
        );

        $this->assertInstanceOf(Service::class, $service);
        $this->assertFalse($service->decorates());
        $this->assertInstanceOf(Tunnel::class, $service->arguments()->last());
    }

    public function testDecorateWithSpecificName()
    {
        $dependencies = new Dependencies(
            new Dependency(
                new Name('dep'),
                new Services(
                    new Arguments,
                    new Dependencies,
                    (new Service(
                        new Name('foo'),
                        $this->createMock(Constructor::class),
                        new Decorate,
                        new Primitive(24)
                    ))->exposeAs(new Name('bar'))
                )
            )
        );

        $service = $dependencies->decorate(
            new Name('dep.bar'),
            new Name('decorated'),
            new Name('watev')
        );

        $this->assertInstanceOf(Service::class, $service);
        $this->assertFalse($service->decorates());
        $this->assertSame('watev', (string) $service->name());
        $this->assertInstanceOf(Tunnel::class, $service->arguments()->last());
    }

    public function testThrowWhenDecoratingUnknownService()
    {
        $dependencies = new Dependencies(
            new Dependency(
                new Name('dep'),
                new Services(
                    new Arguments,
                    new Dependencies,
                    new Service(
                        new Name('foo'),
                        $this->createMock(Constructor::class),
                        new Decorate,
                        new Primitive(24)
                    )
                )
            )
        );

        $this->expectException(ReferenceNotFound::class);
        $this->expectExceptionMessage('dep.bar');

        $dependencies->decorate(
            new Name('dep.bar'),
            new Name('decorated')
        );
    }

    public function testThrowWhenDecoratingUnknownDependency()
    {
        $dependencies = new Dependencies;

        $this->expectException(ReferenceNotFound::class);
        $this->expectExceptionMessage('dep.bar');

        $dependencies->decorate(
            new Name('dep.bar'),
            new Name('decorated')
        );
    }

    public function testThrowWhenDecoratingNonNamespacedName()
    {
        $dependencies = new Dependencies(
            new Dependency(
                new Name('dep'),
                new Services(
                    new Arguments,
                    new Dependencies,
                    new Service(
                        new Name('foo'),
                        $this->createMock(Constructor::class),
                        new Decorate,
                        new Primitive(24)
                    )
                )
            )
        );

        $this->expectException(ReferenceNotFound::class);
        $this->expectExceptionMessage('dep');

        $dependencies->decorate(
            new Name('dep'),
            new Name('decorated')
        );
    }

    public function testExtract()
    {
        $dependencies = new Dependencies(
            new Dependency(
                new Name('dep'),
                new Services(
                    new Arguments,
                    new Dependencies,
                    (new Service(
                        new Name('foo'),
                        $this->createMock(Constructor::class),
                        new Decorate,
                        $argument = new Primitive(24)
                    ))->exposeAs(new Name('bar'))
                )
            )
        );

        $arguments = $dependencies->extract(
            new Name('dep.bar'),
            new Stream('mixed'),
            $argument
        );

        $this->assertInstanceOf(StreamInterface::class, $arguments);
        $this->assertSame('mixed', (string) $arguments->type());
        $this->assertCount(1, $arguments);
        $this->assertSame(24, $arguments->first());
    }

    public function testThrowWhenExtractingArgumentFromNonExposedService()
    {
        $dependencies = new Dependencies(
            new Dependency(
                new Name('dep'),
                new Services(
                    new Arguments,
                    new Dependencies,
                    new Service(
                        new Name('foo'),
                        $this->createMock(Constructor::class),
                        new Decorate,
                        $argument = new Primitive(24)
                    )
                )
            )
        );

        $this->expectException(ReferenceNotFound::class);
        $this->expectExceptionMessage('dep.foo');

        $arguments = $dependencies->extract(
            new Name('dep.foo'),
            new Stream('mixed'),
            $argument
        );
    }

    public function testThrowWhenExtractingArgumentFromUnknownService()
    {
        $dependencies = new Dependencies(
            new Dependency(
                new Name('dep'),
                new Services(
                    new Arguments,
                    new Dependencies,
                    new Service(
                        new Name('foo'),
                        $this->createMock(Constructor::class),
                        new Decorate,
                        $argument = new Primitive(24)
                    )
                )
            )
        );

        $this->expectException(ReferenceNotFound::class);
        $this->expectExceptionMessage('dep.bar');

        $arguments = $dependencies->extract(
            new Name('dep.bar'),
            new Stream('mixed'),
            $argument
        );
    }

    public function testThrowWhenExtractingArgumentFromUnknownDependency()
    {
        $dependencies = new Dependencies;

        $this->expectException(ReferenceNotFound::class);
        $this->expectExceptionMessage('dep.bar');

        $arguments = $dependencies->extract(
            new Name('dep.bar'),
            new Stream('mixed'),
            new Primitive(42)
        );
    }

    public function testThrowWhenExtractingArgumentWithNonNamespacedName()
    {
        $dependencies = new Dependencies;

        $this->expectException(ReferenceNotFound::class);
        $this->expectExceptionMessage('dep');

        $arguments = $dependencies->extract(
            new Name('dep'),
            new Stream('mixed'),
            new Primitive(42)
        );
    }

    public function testExposed()
    {
        $dependencies = new Dependencies(
            new Dependency(
                $dep = new Name('foo'),
                new Services(
                    new Arguments,
                    new Dependencies,
                    new Service(
                        new Name('foo'),
                        $this->createMock(Constructor::class)
                    ),
                    (new Service(
                        new Name('bar'),
                        $expected = $this->createMock(Constructor::class)
                    ))->exposeAs($key = new Name('baz'))
                )
            )
        );

        $exposed = $dependencies->exposed();

        $this->assertInstanceOf(MapInterface::class, $exposed);
        $this->assertSame(Name::class, (string) $exposed->keyType());
        $this->assertSame(MapInterface::class, (string) $exposed->valueType());
        $this->assertCount(1, $exposed);
        $this->assertSame(Name::class, (string) $exposed->get($dep)->keyType());
        $this->assertSame(Constructor::class, (string) $exposed->get($dep)->valueType());
        $this->assertSame($expected, $exposed->get($dep)->get($key));
    }
}
