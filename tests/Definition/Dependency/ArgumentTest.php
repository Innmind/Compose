<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose\Definition\Dependency;

use Innmind\Compose\{
    Definition\Dependency\Argument,
    Definition\Dependency,
    Definition\Argument as Arg,
    Definition\Argument\Type\Primitive,
    Definition\Name,
    Definition\Service,
    Definition\Service\Constructor\Construct,
    Services,
    Arguments,
    Dependencies,
    Exception\ArgumentNotProvided
};
use Innmind\Immutable\{
    Map,
    Str
};
use PHPUnit\Framework\TestCase;

class ArgumentTest extends TestCase
{
    private $services;

    public function setUp()
    {
        $services = new Services(
            new Arguments(
                new Arg(
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
        $argument = Argument::fromValue(
            $name = new Name('foo'),
            42
        );

        $this->assertInstanceOf(Argument::class, $argument);
        $this->assertSame($name, $argument->name());
        $this->assertSame(42, $argument->resolve($this->services));
    }

    public function testBuildStringValue()
    {
        $argument = Argument::fromValue(
            $name = new Name('foo'),
            '42'
        );

        $this->assertInstanceOf(Argument::class, $argument);
        $this->assertSame($name, $argument->name());
        $this->assertSame('42', $argument->resolve($this->services));
    }

    public function testBuildArgumentReference()
    {
        $argument = Argument::fromValue(
            $name = new Name('foo'),
            '$arg'
        );

        $this->assertInstanceOf(Argument::class, $argument);
        $this->assertSame($name, $argument->name());
        $this->assertSame(
            'some random arg',
            $argument->resolve($this->services)
        );
    }

    public function testBuildArgumentReferenceWithDefaultValue()
    {
        $services = new Services(
            new Arguments(
                (new Arg(
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

        $argument = Argument::fromValue(
            $name = new Name('foo'),
            '$arg'
        );

        $this->assertInstanceOf(Argument::class, $argument);
        $this->assertSame($name, $argument->name());
        $this->assertSame(
            $services->build(new Name('default')),
            $argument->resolve($services)
        );
    }

    public function testBuildOptionalArgumentReference()
    {
        $services = new Services(
            new Arguments(
                (new Arg(
                    new Name('arg'),
                    new Primitive('string')
                ))->makeOptional()
            ),
            new Dependencies
        );

        $argument = Argument::fromValue(
            $name = new Name('foo'),
            '$arg'
        );

        $this->assertInstanceOf(Argument::class, $argument);
        $this->assertSame($name, $argument->name());
        $this->assertNull($argument->resolve($services));
    }

    public function testThrowWhenResolvingNonProvidedArgument()
    {
        $services = new Services(
            new Arguments(
                new Arg(
                    new Name('arg'),
                    new Primitive('string')
                )
            ),
            new Dependencies
        );

        $argument = Argument::fromValue(
            $name = new Name('foo'),
            '$arg'
        );

        $this->assertInstanceOf(Argument::class, $argument);
        $this->assertSame($name, $argument->name());

        $this->expectException(ArgumentNotProvided::class);

        $argument->resolve($services);
    }

    public function testBuildServiceReference()
    {
        $argument = Argument::fromValue(
            $name = new Name('foo'),
            '$bar'
        );

        $this->assertInstanceOf(Argument::class, $argument);
        $this->assertSame($name, $argument->name());
        $this->assertSame(
            $this->services->build(new Name('bar')),
            $argument->resolve($this->services)
        );
    }

    public function testDoesntReferToWhenArgumentIsRawValue()
    {
        $argument = Argument::fromValue(
            new Name('foo'),
            42
        );

        $this->assertFalse($argument->refersTo(
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
        $argument = Argument::fromValue(
            new Name('foo'),
            '$foo'
        );

        $this->assertFalse($argument->refersTo(
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
        $argument = Argument::fromValue(
            new Name('foo'),
            '$bar.foo'
        );

        $this->assertFalse($argument->refersTo(
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
        $argument = Argument::fromValue(
            new Name('foo'),
            '$foo.foo'
        );

        $this->assertTrue($argument->refersTo(
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
}
