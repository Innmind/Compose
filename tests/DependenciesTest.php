<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose;

use Innmind\Compose\{
    Dependencies,
    Definition\Dependency,
    Definition\Dependency\Argument,
    Definition\Name,
    Definition\Service,
    Definition\Service\Constructor\Construct,
    Definition\Service\Argument\Primitive,
    Definition\Service\Argument\Reference,
    Definition\Argument as Arg,
    Definition\Argument\Type\Instance,
    Services,
    Arguments,
    Lazy,
    Exception\ReferenceNotFound,
    Exception\ArgumentNotProvided
};
use Innmind\Immutable\Str;
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
                        new Arg(
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
                Argument::fromValue(
                    new Name('arg'),
                    '$std'
                )
            )
        );
        $upper = new Services(
            new Arguments,
            new Dependencies,
            new Service(
                new Name('std'),
                Construct::fromString(Str::of('stdClass'))
            )
        );

        $dependencies2 = $dependencies->bind($upper);

        $this->assertInstanceOf(Dependencies::class, $dependencies2);
        $this->assertNotSame($dependencies2, $dependencies);

        $service = $dependencies2->build(new Name('first.bar'));

        $this->assertInstanceOf(ServiceFixture::class, $service);

        //assert the original object is not aware of the binding
        $this->expectException(ArgumentNotProvided::class);
        $this->expectExceptionMessage('arg');

        $dependencies->build(new Name('first.bar'));
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
}
