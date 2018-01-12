<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose;

use Innmind\Compose\{
    Definitions,
    Arguments,
    Definition\Argument,
    Definition\Name,
    Definition\Argument\Type\Primitive,
    Definition\Argument\Type\Instance,
    Definition\Service,
    Definition\Service\Constructor,
    Exception\AtLeastOneServiceMustBeExposed,
    Exception\CircularDependency
};
use Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;
use Fixture\Innmind\Compose\ServiceFixture;

class DefinitionsTest extends TestCase
{
    public function testInterface()
    {
        $definitions = new Definitions(
            $arguments = new Arguments,
            $service = (new Service(
                new Name('foo'),
                new Constructor('stdClass')
            ))->exposeAs(new Name('baz'))
        );

        $this->assertSame($arguments, $definitions->arguments());
        $this->assertTrue($definitions->has(new Name('foo')));
        $this->assertTrue($definitions->has(new Name('baz')));
        $this->assertFalse($definitions->has(new Name('bar')));
        $this->assertSame($service, $definitions->get(new Name('foo')));
        $this->assertSame($service, $definitions->get(new Name('baz')));
    }

    public function testInject()
    {
        $definitions = new Definitions(
            $arguments = new Arguments(
                new Argument(
                    new Name('baz'),
                    new Primitive('string')
                )
            ),
            $service = (new Service(
                new Name('foo'),
                new Constructor('stdClass')
            ))->exposeAs(new Name('watev'))
        );

        $definitions2 = $definitions->inject(Map::of(
            'string',
            'mixed',
            ['baz'],
            ['42']
        ));

        $this->assertInstanceOf(Definitions::class, $definitions2);
        $this->assertNotSame($definitions, $definitions2);
        $this->assertNotSame($definitions->arguments(), $definitions2->arguments());
        $this->assertSame($arguments, $definitions->arguments());
        $this->assertNotSame($arguments, $definitions2->arguments());
        $this->assertSame('42', $definitions2->arguments()->get(new Name('baz')));
    }

    public function testBuild()
    {
        $definitions = new Definitions(
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
            (new Service(
                new Name('wished'),
                new Constructor(ServiceFixture::class),
                Service\Argument\Reference::fromValue('$firstArg'),
                Service\Argument\Reference::fromValue('$secondArg'),
                Service\Argument\Unwind::fromValue('...$thirdArg')
            ))->exposeAs(new Name('watev')),
            new Service(
                new Name('defaultStd'),
                new Constructor('stdClass')
            )
        );

        $service = $definitions
            ->inject(Map::of('string', 'mixed', ['firstArg'], [42]))
            ->build(new Name('wished'));

        $this->assertInstanceOf(ServiceFixture::class, $service);
        $this->assertSame(42, $service->first);
        $this->assertInstanceOf('stdClass', $service->second);
        $this->assertSame([], $service->third);

        $service = $definitions
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

        $service = $definitions
            ->inject(Map::of('string', 'mixed', ['firstArg', 'thirdArg'], [42, [1, 2, 3]]))
            ->build(new Name('wished'));

        $this->assertInstanceOf(ServiceFixture::class, $service);
        $this->assertSame(42, $service->first);
        $this->assertInstanceOf('stdClass', $service->second);
        $this->assertSame([1, 2, 3], $service->third);
    }

    public function testBuildViaExposedName()
    {
        $definitions = new Definitions(
            new Arguments,
            (new Service(
                new Name('foo'),
                new Constructor('stdClass')
            ))->exposeAs(new Name('bar'))
        );

        $this->assertInstanceOf('stdClass', $definitions->build(new Name('foo')));
        $this->assertInstanceOf('stdClass', $definitions->build(new Name('bar')));
    }

    public function testBuildWithADirectDependencyToAnotherService()
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
        $definitions = $definitions->inject(Map::of('string', 'mixed', ['firstArg'], [42]));

        $this->assertInstanceOf(ServiceFixture::class, $definitions->build(new Name('wished')));
    }

    public function testThrowWhenNoServiceExposed()
    {
        $this->expectException(AtLeastOneServiceMustBeExposed::class);

        new Definitions(new Arguments);
    }

    public function testBuildWithAPrimitiveArgument()
    {
        $definitions = new Definitions(
            new Arguments,
            (new Service(
                new Name('wished'),
                new Constructor(ServiceFixture::class),
                Service\Argument\Primitive::fromValue(42),
                Service\Argument\Reference::fromValue('$defaultStd')
            ))->exposeAs(new Name('watev')),
            new Service(
                new Name('defaultStd'),
                new Constructor('stdClass')
            )
        );

        $service = $definitions->build(new Name('watev'));

        $this->assertInstanceOf(ServiceFixture::class, $service);
        $this->assertSame(42, $service->first);
    }

    public function testThrowExceptionWhenCircularDependencyFound()
    {
        $this->expectException(CircularDependency::class);
        $this->expectExceptionMessage('foo -> bar -> foo');

        $definitions = new Definitions(
            new Arguments,
            (new Service(
                new Name('foo'),
                new Constructor('stdClass'),
                Service\Argument\Reference::fromValue('$bar')
            ))->exposeAs(new Name('watev')),
            new Service(
                new Name('bar'),
                new Constructor('stdClass'),
                Service\Argument\Reference::fromValue('$foo')
            )
        );

        $definitions->build(new Name('foo'));
    }

    public function testDoesntRethrowWhenBuildingAValidServiceAfterACircularDependencyFound()
    {
        $definitions = new Definitions(
            new Arguments,
            (new Service(
                new Name('foo'),
                new Constructor('stdClass'),
                Service\Argument\Reference::fromValue('$baz'),
                Service\Argument\Reference::fromValue('$bar')
            ))->exposeAs(new Name('watev')),
            new Service(
                new Name('bar'),
                new Constructor('stdClass'),
                Service\Argument\Reference::fromValue('$foo')
            ),
            new Service(
                new Name('baz'),
                new Constructor('stdClass')
            )
        );

        try {
            $definitions->build(new Name('foo'));

            $this->fail('it should throw');
        } catch (CircularDependency $e) {
            //pass
        }

        $this->assertInstanceOf('stdClass', $definitions->build(new Name('baz')));
    }
}
