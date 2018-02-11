<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose\Definition\Dependency;

use Innmind\Compose\{
    Definition\Dependency\Parameter,
    Definition\Dependency,
    Definition\Argument,
    Definition\Argument\Type\Primitive,
    Definition\Name,
    Definition\Service,
    Definition\Service\Constructor\Construct,
    Services,
    Arguments,
    Dependencies,
    Exception\ArgumentNotProvided,
    Compilation\Dependency\Parameter as CompiledParameter
};
use Innmind\Immutable\{
    Map,
    Str
};
use PHPUnit\Framework\TestCase;

class ParameterTest extends TestCase
{
    private $services;

    public function setUp()
    {
        $services = new Services(
            new Arguments(
                new Argument(
                    new Name('arg'),
                    new Primitive('string')
                )
            ),
            new Dependencies,
            new Service(
                new Name('bar'),
                Construct::fromString(Str::of('stdClass'))
            )
        );
        $this->services = $services->inject(
            (new Map('string', 'mixed'))
                ->put('arg', 'some random arg')
        );
    }

    public function testBuildRawValue()
    {
        $parameter = Parameter::fromValue(
            $name = new Name('foo'),
            42
        );

        $this->assertInstanceOf(Parameter::class, $parameter);
        $this->assertSame($name, $parameter->name());
        $this->assertSame(42, $parameter->resolve($this->services));
    }

    public function testBuildStringValue()
    {
        $parameter = Parameter::fromValue(
            $name = new Name('foo'),
            '42'
        );

        $this->assertInstanceOf(Parameter::class, $parameter);
        $this->assertSame($name, $parameter->name());
        $this->assertSame('42', $parameter->resolve($this->services));
    }

    public function testBuildArgumentReference()
    {
        $parameter = Parameter::fromValue(
            $name = new Name('foo'),
            '$arg'
        );

        $this->assertInstanceOf(Parameter::class, $parameter);
        $this->assertSame($name, $parameter->name());
        $this->assertSame(
            'some random arg',
            $parameter->resolve($this->services)
        );
    }

    public function testBuildArgumentReferenceWithDefaultValue()
    {
        $services = new Services(
            new Arguments(
                (new Argument(
                    new Name('arg'),
                    new Primitive('string')
                ))->defaultsTo(new Name('default'))
            ),
            new Dependencies,
            new Service(
                new Name('default'),
                Construct::fromString(Str::of('stdClass'))
            )
        );

        $parameter = Parameter::fromValue(
            $name = new Name('foo'),
            '$arg'
        );

        $this->assertInstanceOf(Parameter::class, $parameter);
        $this->assertSame($name, $parameter->name());
        $this->assertSame(
            $services->build(new Name('default')),
            $parameter->resolve($services)
        );
    }

    public function testBuildOptionalArgumentReference()
    {
        $services = new Services(
            new Arguments(
                (new Argument(
                    new Name('arg'),
                    new Primitive('string')
                ))->makeOptional()
            ),
            new Dependencies
        );

        $parameter = Parameter::fromValue(
            $name = new Name('foo'),
            '$arg'
        );

        $this->assertInstanceOf(Parameter::class, $parameter);
        $this->assertSame($name, $parameter->name());
        $this->assertNull($parameter->resolve($services));
    }

    public function testThrowWhenResolvingNonProvidedArgument()
    {
        $services = new Services(
            new Arguments(
                new Argument(
                    new Name('arg'),
                    new Primitive('string')
                )
            ),
            new Dependencies
        );

        $parameter = Parameter::fromValue(
            $name = new Name('foo'),
            '$arg'
        );

        $this->assertInstanceOf(Parameter::class, $parameter);
        $this->assertSame($name, $parameter->name());

        $this->expectException(ArgumentNotProvided::class);

        $parameter->resolve($services);
    }

    public function testBuildServiceReference()
    {
        $parameter = Parameter::fromValue(
            $name = new Name('foo'),
            '$bar'
        );

        $this->assertInstanceOf(Parameter::class, $parameter);
        $this->assertSame($name, $parameter->name());
        $this->assertSame(
            $this->services->build(new Name('bar')),
            $parameter->resolve($this->services)
        );
    }

    public function testBuildServiceFromAnotherDependency()
    {
        $value = Parameter::fromValue(new Name('foo'), '$dep.bar')->resolve(new Services(
            new Arguments,
            $dependencies = new Dependencies(
                new Dependency(
                    new Name('dep'),
                    new Services(
                        new Arguments,
                        new Dependencies,
                        (new Service(
                            new Name('foo'),
                            Construct::fromString(Str::of('stdClass'))
                        ))->exposeAs(new Name('bar'))
                    )
                )
            )
        ));

        $this->assertInstanceOf('stdClass', $value);
        $this->assertSame($dependencies->build(new Name('dep.bar')), $value);
    }

    public function testDoesntReferToWhenArgumentIsRawValue()
    {
        $parameter = Parameter::fromValue(
            new Name('foo'),
            42
        );

        $this->assertFalse($parameter->refersTo(
            new Dependency(
                new Name('foo'),
                new Services(
                    new Arguments,
                    new Dependencies
                )
            )
        ));
    }

    public function testDoesntReferToWhenArgumentReferenceIsNotNamespaced()
    {
        $parameter = Parameter::fromValue(
            new Name('foo'),
            '$foo'
        );

        $this->assertFalse($parameter->refersTo(
            new Dependency(
                new Name('foo'),
                new Services(
                    new Arguments,
                    new Dependencies,
                    (new Service(
                        new Name('bar'),
                        Construct::fromString(Str::of('stdClass'))
                    ))->exposeAs(new Name('foo'))
                )
            )
        ));
    }

    public function testDoesntReferToWhenArgumentReferenceRootDifferentThanDependencyName()
    {
        $parameter = Parameter::fromValue(
            new Name('foo'),
            '$bar.foo'
        );

        $this->assertFalse($parameter->refersTo(
            new Dependency(
                new Name('foo'),
                new Services(
                    new Arguments,
                    new Dependencies,
                    (new Service(
                        new Name('bar'),
                        Construct::fromString(Str::of('stdClass'))
                    ))->exposeAs(new Name('foo'))
                )
            )
        ));
    }

    public function testRefersTo()
    {
        $parameter = Parameter::fromValue(
            new Name('foo'),
            '$foo.foo'
        );

        $this->assertTrue($parameter->refersTo(
            new Dependency(
                new Name('foo'),
                new Services(
                    new Arguments,
                    new Dependencies,
                    (new Service(
                        new Name('bar'),
                        Construct::fromString(Str::of('stdClass'))
                    ))->exposeAs(new Name('foo'))
                )
            )
        ));
    }

    public function testCompile()
    {
        $parameter = Parameter::fromValue(
            new Name('foo'),
            '$foo.foo'
        );

        $this->assertInstanceOf(CompiledParameter::class, $parameter->compile());
    }
}
