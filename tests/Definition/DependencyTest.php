<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose\Definition;

use Innmind\Compose\{
    Definition\Dependency,
    Definition\Dependency\Argument,
    Definition\Argument as Arg,
    Definition\Argument\Type\Primitive,
    Definition\Name,
    Definition\Service,
    Definition\Service\Constructor\Construct,
    Definition\Service\Argument\Reference,
    Services,
    Arguments,
    Exception\ReferenceNotFound,
    Exception\ArgumentNotProvided
};
use Innmind\Immutable\{
    Map,
    Str
};
use PHPUnit\Framework\TestCase;
use Fixture\Innmind\Compose\ServiceFixture;

class DependencyTest extends TestCase
{
    public function testInterface()
    {
        $dependency = new Dependency(
            $name = new Name('foo'),
            new Services(new Arguments)
        );

        $this->assertSame($name, $dependency->name());
    }

    public function testBuild()
    {
        $dependency = new Dependency(
            new Name('watev'),
            $services = new Services(
                new Arguments,
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
                    new Arg(
                        new Name('innerArg'),
                        new Primitive('int')
                    )
                ),
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
            Argument::fromValue(new Name('innerArg'), '$arg')
        );

        $upper = (new Services(
            new Arguments(
                new Arg(
                    new Name('arg'),
                    new Primitive('int')
                )
            )
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
}
