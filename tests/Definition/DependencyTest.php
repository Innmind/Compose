<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose\Definition;

use Innmind\Compose\{
    Definition\Dependency,
    Definition\Dependency\Parameter,
    Definition\Argument,
    Definition\Argument\Type\Primitive,
    Definition\Name,
    Definition\Service,
    Definition\Service\Constructor\Construct,
    Definition\Service\Constructor,
    Definition\Service\Argument\Reference,
    Definition\Service\Argument\Decorate,
    Definition\Service\Argument\Tunnel,
    Services,
    Arguments,
    Dependencies,
    Lazy,
    Exception\ReferenceNotFound,
    Exception\ArgumentNotProvided,
    Exception\ArgumentNotExtractable,
    Compilation\Dependency as CompiledDependency
};
use Innmind\Immutable\{
    MapInterface,
    Map,
    Str,
    StreamInterface,
    Stream
};
use PHPUnit\Framework\TestCase;
use Fixture\Innmind\Compose\ServiceFixture;

class DependencyTest extends TestCase
{
    public function testInterface()
    {
        $dependency = new Dependency(
            $name = new Name('foo'),
            new Services(new Arguments, new Dependencies)
        );

        $this->assertSame($name, $dependency->name());
    }

    public function testBuild()
    {
        $dependency = new Dependency(
            new Name('watev'),
            $services = new Services(
                new Arguments,
                new Dependencies,
                (new Service(
                    new Name('foo'),
                    Construct::fromString(Str::of('stdClass'))
                ))->exposeAs(new Name('bar'))
            )
        );

        $this->assertSame(
            $services->build(new Name('foo')),
            $dependency->build(new Name('bar'))
        );
    }

    public function testThrowWhenTryingToBuildWithInnerServiceName()
    {
        $dependency = new Dependency(
            new Name('watev'),
            $services = new Services(
                new Arguments,
                new Dependencies,
                (new Service(
                    new Name('foo'),
                    Construct::fromString(Str::of('stdClass'))
                ))->exposeAs(new Name('bar'))
            )
        );

        $this->expectException(ReferenceNotFound::class);
        $this->expectExceptionMessage('foo');

        $dependency->build(new Name('foo'));
    }

    public function testThrowWhenTryingToBuildNonExposedService()
    {
        $dependency = new Dependency(
            new Name('watev'),
            $services = new Services(
                new Arguments,
                new Dependencies,
                new Service(
                    new Name('foo'),
                    Construct::fromString(Str::of('stdClass'))
                )
            )
        );

        $this->expectException(ReferenceNotFound::class);
        $this->expectExceptionMessage('foo');

        $dependency->build(new Name('foo'));
    }

    public function testBind()
    {
        $dependency = new Dependency(
            new Name('watev'),
            new Services(
                new Arguments(
                    new Argument(
                        new Name('innerArg'),
                        new Primitive('int')
                    )
                ),
                new Dependencies,
                (new Service(
                    new Name('foo'),
                    Construct::fromString(Str::of(ServiceFixture::class)),
                    new Reference(new Name('innerArg')),
                    new Reference(new Name('std'))
                ))->exposeAs(new Name('bar')),
                new Service(
                    new Name('std'),
                    Construct::fromString(Str::of('stdClass'))
                )
            ),
            Parameter::fromValue(new Name('innerArg'), '$arg')
        );

        $upper = (new Services(
            new Arguments(
                new Argument(
                    new Name('arg'),
                    new Primitive('int')
                )
            ),
            new Dependencies
        ))->inject((new Map('string', 'mixed'))->put('arg', 42));

        $dependency2 = $dependency->bind($upper);

        $this->assertInstanceOf(Dependency::class, $dependency2);
        $this->assertNotSame($dependency2, $dependency);

        $service = $dependency2->build(new Name('bar'));

        $this->assertInstanceOf(ServiceFixture::class, $service);
        $this->assertSame(42, $service->first);
        $this->assertInstanceOf('stdClass', $service->second);
        $this->assertSame([], $service->third);

        //verify the initial dependency is not aware of the binding
        $this->expectException(ArgumentNotProvided::class);

        $dependency->build(new Name('bar'));
    }

    public function testLazy()
    {
        $dependency = new Dependency(
            new Name('foo'),
            new Services(
                new Arguments,
                new Dependencies,
                (new Service(
                    new Name('foo'),
                    Construct::fromString(Str::of('stdClass'))
                ))->exposeAs(new Name('bar'))
            )
        );

        $service = $dependency->lazy(new Name('bar'));

        $this->assertInstanceOf(Lazy::class, $service);
        $this->assertInstanceOf('stdClass', $service->load());
    }

    public function testThrowWhenTryingToLazyLoadNonExposedService()
    {
        $dependency = new Dependency(
            new Name('foo'),
            new Services(
                new Arguments,
                new Dependencies,
                new Service(
                    new Name('foo'),
                    Construct::fromString(Str::of('stdClass'))
                )
            )
        );

        $this->expectException(ReferenceNotFound::class);
        $this->expectExceptionMessage('bar');

        $dependency->lazy(new Name('bar'));
    }

    public function testThrowWhenTryingToLazyLoadServiceWithItsInnerName()
    {
        $dependency = new Dependency(
            new Name('foo'),
            new Services(
                new Arguments,
                new Dependencies,
                (new Service(
                    new Name('foo'),
                    Construct::fromString(Str::of('stdClass'))
                ))->exposeAs(new Name('bar'))
            )
        );

        $this->expectException(ReferenceNotFound::class);
        $this->expectExceptionMessage('foo');

        $dependency->lazy(new Name('foo'));
    }

    public function testHas()
    {
        $dependency = new Dependency(
            new Name('foo'),
            new Services(
                new Arguments,
                new Dependencies,
                (new Service(
                    new Name('foo'),
                    Construct::fromString(Str::of('stdClass'))
                ))->exposeAs(new Name('bar'))
            )
        );

        $this->assertTrue($dependency->has(new Name('bar')));
        $this->assertFalse($dependency->has(new Name('foo')));
        $this->assertFalse($dependency->has(new Name('baz')));
    }

    public function testDependsOn()
    {
        $dependency = new Dependency(
            new Name('foo'),
            new Services(
                new Arguments,
                new Dependencies
            ),
            Parameter::fromValue(new Name('first'), 42),
            Parameter::fromValue(new Name('seconf'), '$bar.bar'),
            Parameter::fromValue(new Name('thrid'), 24)
        );
        $other = new Dependency(
            new Name('bar'),
            new Services(
                new Arguments,
                new Dependencies,
                (new Service(
                    new Name('foo'),
                    Construct::fromString(Str::of('stdClass'))
                ))->exposeAs(new Name('bar'))
            )
        );

        $this->assertTrue($dependency->dependsOn($other));
        $this->assertFalse($dependency->dependsOn($dependency));
    }

    public function testDecorate()
    {
        $dependency = new Dependency(
            new Name('dep'),
            new Services(
                new Arguments,
                new Dependencies,
                (new Service(
                    new Name('foo'),
                    $this->createMock(Constructor::class),
                    new Decorate,
                    new Reference(new Name('unknown'))
                ))->exposeAs(new Name('bar'))
            )
        );
        $service = $dependency->decorate(
            new Name('bar'),
            new Name('decorated'),
            new Name('watev')
        );

        $this->assertInstanceOf(Service::class, $service);
        $this->assertSame('watev', (string) $service->name());
        $this->assertInstanceOf(Reference::class, $service->arguments()->first());
        $this->assertInstanceOf(Tunnel::class, $service->arguments()->last());
    }

    public function testThrowWhenDecoratingUnknownService()
    {
        $dependency = new Dependency(
            new Name('dep'),
            new Services(
                new Arguments,
                new Dependencies
            )
        );

        $this->expectException(ReferenceNotFound::class);
        $this->expectExceptionMessage('bar');

        $dependency->decorate(
            new Name('bar'),
            new Name('decorated'),
            new Name('watev')
        );
    }

    public function testThrowWhenDecoratingNonExposedService()
    {
        $dependency = new Dependency(
            new Name('dep'),
            new Services(
                new Arguments,
                new Dependencies,
                new Service(
                    new Name('foo'),
                    $this->createMock(Constructor::class),
                    new Decorate,
                    new Reference(new Name('unknown'))
                )
            )
        );

        $this->expectException(ReferenceNotFound::class);
        $this->expectExceptionMessage('foo');

        $dependency->decorate(
            new Name('foo'),
            new Name('decorated'),
            new Name('watev')
        );
    }

    public function testThrowWhenDecoratingServiceViaItsInnerName()
    {
        $dependency = new Dependency(
            new Name('dep'),
            new Services(
                new Arguments,
                new Dependencies,
                (new Service(
                    new Name('foo'),
                    $this->createMock(Constructor::class),
                    new Decorate,
                    new Reference(new Name('unknown'))
                ))->exposeAs(new Name('bar'))
            )
        );

        $this->expectException(ReferenceNotFound::class);
        $this->expectExceptionMessage('foo');

        $dependency->decorate(
            new Name('foo'),
            new Name('decorated'),
            new Name('watev')
        );
    }

    public function testExtract()
    {
        $dependency = new Dependency(
            new Name('dep'),
            new Services(
                new Arguments,
                new Dependencies,
                new Service(
                    new Name('std'),
                    Construct::fromString(Str::of('stdClass'))
                ),
                (new Service(
                    new Name('foo'),
                    $this->createMock(Constructor::class),
                    new Decorate,
                    $argument = new Reference(new Name('std'))
                ))->exposeAs(new Name('bar'))
            )
        );

        $arguments = $dependency->extract(
            new Name('bar'),
            new Stream('mixed'),
            $argument
        );

        $this->assertInstanceOf(StreamInterface::class, $arguments);
        $this->assertSame('mixed', (string) $arguments->type());
        $this->assertCount(1, $arguments);
        $this->assertInstanceOf(Lazy::class, $arguments->first());
        $this->assertInstanceOf('stdClass', $arguments->first()->load());
    }

    public function testThrowWhenExtractingArgumentFromADifferentService()
    {
        $dependency = new Dependency(
            new Name('dep'),
            new Services(
                new Arguments,
                new Dependencies,
                new Service(
                    new Name('std'),
                    Construct::fromString(Str::of('stdClass'))
                ),
                (new Service(
                    new Name('foo'),
                    $this->createMock(Constructor::class),
                    new Decorate,
                    new Reference(new Name('std'))
                ))->exposeAs(new Name('bar'))
            )
        );

        $this->expectException(ArgumentNotExtractable::class);

        $dependency->extract(
            new Name('bar'),
            new Stream('mixed'),
            $this->createMock(Service\Argument::class)
        );
    }

    public function testThrowWhenExtractingArgumentFromAServiceThatDoNotDecorates()
    {
        $dependency = new Dependency(
            new Name('dep'),
            new Services(
                new Arguments,
                new Dependencies,
                new Service(
                    new Name('std'),
                    Construct::fromString(Str::of('stdClass'))
                ),
                (new Service(
                    new Name('foo'),
                    $this->createMock(Constructor::class),
                    $argument = new Reference(new Name('std'))
                ))->exposeAs(new Name('bar'))
            )
        );

        $this->expectException(ArgumentNotExtractable::class);

        $dependency->extract(
            new Name('bar'),
            new Stream('mixed'),
            $argument
        );
    }

    public function testThrowWhenExtractingArgumentFromAServiceViaItsInnerName()
    {
        $dependency = new Dependency(
            new Name('dep'),
            new Services(
                new Arguments,
                new Dependencies,
                new Service(
                    new Name('std'),
                    Construct::fromString(Str::of('stdClass'))
                ),
                (new Service(
                    new Name('foo'),
                    $this->createMock(Constructor::class),
                    $argument = new Reference(new Name('std'))
                ))->exposeAs(new Name('bar'))
            )
        );

        $this->expectException(ReferenceNotFound::class);
        $this->expectExceptionMessage('foo');

        $dependency->extract(
            new Name('foo'),
            new Stream('mixed'),
            $argument
        );
    }

    public function testThrowWhenExtractingArgumentFromNonExposedService()
    {
        $dependency = new Dependency(
            new Name('dep'),
            new Services(
                new Arguments,
                new Dependencies,
                new Service(
                    new Name('std'),
                    Construct::fromString(Str::of('stdClass'))
                ),
                new Service(
                    new Name('foo'),
                    $this->createMock(Constructor::class),
                    $argument = new Reference(new Name('std'))
                )
            )
        );

        $this->expectException(ReferenceNotFound::class);
        $this->expectExceptionMessage('foo');

        $dependency->extract(
            new Name('foo'),
            new Stream('mixed'),
            $argument
        );
    }

    public function testThrowWhenExtractingArgumentFromUnknownService()
    {
        $dependency = new Dependency(
            new Name('dep'),
            new Services(
                new Arguments,
                new Dependencies,
                new Service(
                    new Name('std'),
                    Construct::fromString(Str::of('stdClass'))
                )
            )
        );

        $this->expectException(ReferenceNotFound::class);
        $this->expectExceptionMessage('foo');

        $dependency->extract(
            new Name('foo'),
            new Stream('mixed'),
            new Reference(new Name('std'))
        );
    }

    public function testExposed()
    {
        $dependency = new Dependency(
            new Name('foo'),
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
        );

        $exposed = $dependency->exposed();

        $this->assertInstanceOf(MapInterface::class, $exposed);
        $this->assertSame(Name::class, (string) $exposed->keyType());
        $this->assertSame(Constructor::class, (string) $exposed->valueType());
        $this->assertCount(1, $exposed);
        $this->assertSame($expected, $exposed->get($key));
    }

    public function testCompile()
    {
        $dependency = new Dependency(
            new Name('foo'),
            new Services(
                new Arguments,
                new Dependencies
            )
        );

        $this->assertInstanceOf(CompiledDependency::class, $dependency->compile());
    }
}
