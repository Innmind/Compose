<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose;

use Innmind\Compose\{
    Services,
    Arguments,
    Dependencies,
    Definition\Argument,
    Definition\Name,
    Definition\Argument\Type\Primitive,
    Definition\Argument\Type\Instance,
    Definition\Service,
    Definition\Service\Constructor,
    Definition\Service\Arguments as Args,
    Exception\CircularDependency
};
use Innmind\Immutable\{
    Map,
    Str
};
use PHPUnit\Framework\TestCase;
use Fixture\Innmind\Compose\{
    ServiceFixture,
    Stack\Low,
    Stack\Middle,
    Stack\High
};

class ServicesTest extends TestCase
{
    private $args;

    public function setUp()
    {
        $this->args = new Args;
    }

    public function testInterface()
    {
        $services = new Services(
            $arguments = new Arguments,
            $dependencies = new Dependencies,
            $service = (new Service(
                new Name('foo'),
                Constructor\Construct::fromString(Str::of('stdClass'))
            ))->exposeAs(new Name('baz'))
        );

        $this->assertSame($arguments, $services->arguments());
        $this->assertSame($dependencies, $services->dependencies());
        $this->assertTrue($services->has(new Name('foo')));
        $this->assertTrue($services->has(new Name('baz')));
        $this->assertFalse($services->has(new Name('bar')));
        $this->assertSame($service, $services->get(new Name('foo')));
        $this->assertSame($service, $services->get(new Name('baz')));
    }

    public function testInject()
    {
        $services = new Services(
            $arguments = new Arguments(
                new Argument(
                    new Name('baz'),
                    new Primitive('string')
                )
            ),
            new Dependencies,
            $service = (new Service(
                new Name('foo'),
                Constructor\Construct::fromString(Str::of('stdClass'))
            ))->exposeAs(new Name('watev'))
        );

        $services2 = $services->inject(Map::of(
            'string',
            'mixed',
            ['baz'],
            ['42']
        ));

        $this->assertInstanceOf(Services::class, $services2);
        $this->assertNotSame($services, $services2);
        $this->assertNotSame($services->arguments(), $services2->arguments());
        $this->assertSame($arguments, $services->arguments());
        $this->assertNotSame($arguments, $services2->arguments());
        $this->assertSame('42', $services2->arguments()->get(new Name('baz')));
    }

    public function testBuild()
    {
        $services = new Services(
            new Arguments(
                new Argument(
                    new Name('firstArg'),
                    new Primitive('int')
                ),
                (new Argument(
                    new Name('secondArg'),
                    new Instance('stdClass')
                ))->defaultsTo(new Name('defaultStd')),
                (new Argument(
                    new Name('thirdArg'),
                    new Primitive('array')
                ))->makeOptional()
            ),
            new Dependencies,
            (new Service(
                new Name('wished'),
                Constructor\Construct::fromString(Str::of(ServiceFixture::class)),
                $this->args->load('$firstArg'),
                $this->args->load('$secondArg'),
                $this->args->load('...$thirdArg')
            ))->exposeAs(new Name('watev')),
            new Service(
                new Name('defaultStd'),
                Constructor\Construct::fromString(Str::of('stdClass'))
            )
        );

        $service = $services
            ->inject(Map::of('string', 'mixed', ['firstArg'], [42]))
            ->build(new Name('wished'));

        $this->assertInstanceOf(ServiceFixture::class, $service);
        $this->assertSame(42, $service->first);
        $this->assertInstanceOf('stdClass', $service->second);
        $this->assertSame([], $service->third);

        $service = $services
            ->inject(Map::of(
                'string',
                'mixed',
                ['firstArg', 'secondArg'],
                [
                    42,
                    $expected = new \stdClass,
                ]
            ))
            ->build(new Name('wished'));

        $this->assertInstanceOf(ServiceFixture::class, $service);
        $this->assertSame(42, $service->first);
        $this->assertSame($expected, $service->second);
        $this->assertSame([], $service->third);

        $service = $services
            ->inject(Map::of('string', 'mixed', ['firstArg', 'thirdArg'], [42, [1, 2, 3]]))
            ->build(new Name('wished'));

        $this->assertInstanceOf(ServiceFixture::class, $service);
        $this->assertSame(42, $service->first);
        $this->assertInstanceOf('stdClass', $service->second);
        $this->assertSame([1, 2, 3], $service->third);
    }

    public function testBuildViaExposedName()
    {
        $services = new Services(
            new Arguments,
            new Dependencies,
            (new Service(
                new Name('foo'),
                Constructor\Construct::fromString(Str::of('stdClass'))
            ))->exposeAs(new Name('bar'))
        );

        $this->assertInstanceOf('stdClass', $services->build(new Name('foo')));
        $this->assertInstanceOf('stdClass', $services->build(new Name('bar')));
    }

    public function testBuildWithADirectDependencyToAnotherService()
    {
        $services = new Services(
            new Arguments(
                new Argument(
                    new Name('firstArg'),
                    new Primitive('int')
                )
            ),
            new Dependencies,
            (new Service(
                new Name('wished'),
                Constructor\Construct::fromString(Str::of(ServiceFixture::class)),
                $this->args->load('$firstArg'),
                $this->args->load('$defaultStd')
            ))->exposeAs(new Name('foo')),
            new Service(
                new Name('defaultStd'),
                Constructor\Construct::fromString(Str::of('stdClass'))
            )
        );
        $services = $services->inject(Map::of('string', 'mixed', ['firstArg'], [42]));

        $this->assertInstanceOf(ServiceFixture::class, $services->build(new Name('wished')));
    }

    public function testBuildWithAPrimitiveArgument()
    {
        $services = new Services(
            new Arguments,
            new Dependencies,
            (new Service(
                new Name('wished'),
                Constructor\Construct::fromString(Str::of(ServiceFixture::class)),
                $this->args->load(42),
                $this->args->load('$defaultStd')
            ))->exposeAs(new Name('watev')),
            new Service(
                new Name('defaultStd'),
                Constructor\Construct::fromString(Str::of('stdClass'))
            )
        );

        $service = $services->build(new Name('watev'));

        $this->assertInstanceOf(ServiceFixture::class, $service);
        $this->assertSame(42, $service->first);
    }

    public function testThrowExceptionWhenCircularDependencyFound()
    {
        $this->expectException(CircularDependency::class);
        $this->expectExceptionMessage('foo -> bar -> foo');

        $services = new Services(
            new Arguments,
            new Dependencies,
            (new Service(
                new Name('foo'),
                Constructor\Construct::fromString(Str::of('stdClass')),
                $this->args->load('$bar')
            ))->exposeAs(new Name('watev')),
            new Service(
                new Name('bar'),
                Constructor\Construct::fromString(Str::of('stdClass')),
                $this->args->load('$foo')
            )
        );

        $services->build(new Name('foo'));
    }

    public function testDoesntRethrowWhenBuildingAValidServiceAfterACircularDependencyFound()
    {
        $services = new Services(
            new Arguments,
            new Dependencies,
            (new Service(
                new Name('foo'),
                Constructor\Construct::fromString(Str::of('stdClass')),
                $this->args->load('$baz'),
                $this->args->load('$bar')
            ))->exposeAs(new Name('watev')),
            new Service(
                new Name('bar'),
                Constructor\Construct::fromString(Str::of('stdClass')),
                $this->args->load('$foo')
            ),
            new Service(
                new Name('baz'),
                Constructor\Construct::fromString(Str::of('stdClass'))
            )
        );

        try {
            $services->build(new Name('foo'));

            $this->fail('it should throw');
        } catch (CircularDependency $e) {
            //pass
        }

        $this->assertInstanceOf('stdClass', $services->build(new Name('baz')));
    }

    public function testBuildAServiceOnlyOnce()
    {
        $services = new Services(
            new Arguments,
            new Dependencies,
            (new Service(
                new Name('foo'),
                Constructor\Construct::fromString(Str::of('stdClass'))
            ))->exposeAs(new Name('watev'))
        );

        $service = $services->build(new Name('watev'));

        $this->assertInstanceOf('stdClass', $service);
        $this->assertSame($service, $services->build(new Name('watev')));
    }

    public function testReturnSameInstanceWhenCalledEitherByNameOrExposedName()
    {
        $services = new Services(
            new Arguments,
            new Dependencies,
            (new Service(
                new Name('foo'),
                Constructor\Construct::fromString(Str::of('stdClass'))
            ))->exposeAs(new Name('watev'))
        );

        $this->assertSame(
            $services->build(new Name('watev')),
            $services->build(new Name('foo'))
        );
    }

    public function testInstancesAreResettedWhenInjectingNewArguments()
    {
        $services = new Services(
            new Arguments,
            new Dependencies,
            (new Service(
                new Name('foo'),
                Constructor\Construct::fromString(Str::of('stdClass'))
            ))->exposeAs(new Name('watev'))
        );

        $service = $services->build(new Name('watev'));

        $services2 = $services->inject(new Map('string', 'mixed'));

        $this->assertNotSame($service, $services2->build(new Name('watev')));
        $this->assertSame($service, $services->build(new Name('watev')));
    }

    public function testExpose()
    {
        $services = new Services(
            new Arguments,
            new Dependencies,
            $service = new Service(
                new Name('foo'),
                Constructor\Construct::fromString(Str::of('stdClass'))
            )
        );
        $services2 = $services->expose(new Name('foo'), new Name('watev'));

        $this->assertInstanceOf(Services::class, $services2);
        $this->assertNotSame($services2, $services);
        $this->assertFalse($services->get(new Name('foo'))->exposed());
        $this->assertTrue($services2->get(new Name('foo'))->exposed());
        $this->assertTrue($services2->get(new Name('foo'))->isExposedAs(new Name('watev')));
    }

    public function testStack()
    {
        $services = new Services(
            new Arguments,
            new Dependencies,
            new Service(
                new Name('low'),
                Constructor\Construct::fromString(Str::of(Low::class))
            ),
            new Service(
                new Name('middle'),
                Constructor\Construct::fromString(Str::of(Middle::class)),
                $this->args->load('@decorated')
            ),
            new Service(
                new Name('high'),
                Constructor\Construct::fromString(Str::of(High::class)),
                $this->args->load('@decorated')
            )
        );

        $services2 = $services->stack(
            new Name('stack'),
            new Name('high'),
            new Name('middle'),
            new Name('low')
        );

        $this->assertInstanceOf(Services::class, $services2);
        $this->assertNotSame($services2, $services);
        $this->assertFalse($services->has(new Name('stack')));
        $this->assertTrue($services2->has(new Name('stack')));
        $this->assertSame(
            'high|middle|low|middle|high',
            $services2->build(new Name('stack'))()
        );
    }
}
