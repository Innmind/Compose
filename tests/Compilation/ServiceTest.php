<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose\Compilation;

use Innmind\Compose\{
    Compilation\Service,
    Compilation\Service\Constructor,
    Compilation\PropertyName,
    Compilation\MethodName,
    Definition\Service as Definition,
    Definition\Service\Constructor\Construct,
    Definition\Name
};
use Innmind\Immutable\Str;
use PHPUnit\Framework\TestCase;

class ServiceTest extends TestCase
{
    private $definition;
    private $exposed;

    public function setUp()
    {
        $this->definition = new Definition(
            new Name('foo'),
            Construct::fromString(Str::of('stdClass'))
        );
        $this->exposed = $this->definition->exposeAs(new Name('bar'));
    }

    public function testAccessible()
    {
        $service = new Service(
            $this->exposed,
            $this->createMock(Constructor::class)
        );
        $this->assertTrue($service->accessible());

        $service = new Service(
            $this->definition,
            $this->createMock(Constructor::class)
        );
        $this->assertFalse($service->accessible());
    }

    public function testName()
    {
        $service = new Service(
            $this->exposed,
            $this->createMock(Constructor::class)
        );
        $this->assertSame($this->exposed->exposedAs(), $service->name());

        $service = new Service(
            $this->definition,
            $this->createMock(Constructor::class)
        );

        $this->expectException(\TypeError::class);

        $service->name();
    }

    public function testProperty()
    {
        $service = new Service(
            $this->exposed,
            $this->createMock(Constructor::class)
        );

        $this->assertInstanceOf(PropertyName::class, $service->property());
        $this->assertSame('foo', (string) $service->property());
    }

    public function testMethod()
    {
        $service = new Service(
            $this->exposed,
            $this->createMock(Constructor::class)
        );

        $this->assertInstanceOf(MethodName::class, $service->method());
        $this->assertSame('buildFoo', (string) $service->method());
    }

    public function testStringCast()
    {
        $service = new Service(
            $this->exposed,
            $mock = $this->createMock(Constructor::class)
        );
        $mock
            ->expects($this->once())
            ->method('__toString')
            ->willReturn('new stdClass');
        $expected = <<<PHP
    public function buildFoo(): object
    {
        return \$this->foo ?? \$this->foo = new stdClass;
    }
PHP;

        $this->assertSame($expected, (string) $service);
    }
}
