<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose\Compilation;

use Innmind\Compose\{
    Compilation\Dependency,
    Definition\Name,
    Definition\Service,
    Definition\Service\Constructor\Construct,
    Definition\Dependency\Parameter,
    Arguments,
    Dependencies,
    Services
};
use Innmind\Immutable\Str;
use PHPUnit\Framework\TestCase;

class DependencyTest extends TestCase
{
    private $dependency;

    public function setUp()
    {
        $this->dependency = new Dependency(
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
                    Construct::fromString(Str::of('stdClass'))
                ))->exposeAs(new Name('bar'))
            ),
            Parameter::fromValue(new Name('arg'), 42)
        );
    }

    public function testName()
    {
        $this->assertInstanceOf(Name::class, $this->dependency->name());
        $this->assertSame('dep', (string) $this->dependency->name());
    }

    public function testExposed()
    {
        $expected = <<<PHP
    private function buildDepBar()
    {
        return \$this->dep->get('bar');
    }
PHP;

        $this->assertSame($expected, $this->dependency->exposed());
    }

    public function testStringCast()
    {
        $expected = <<<PHP
        \$arguments = (new \Innmind\Immutable\Map('string', 'mixed'))
->put('arg', 42);
        \$this->dep = new class(\$arguments) implements ContainerInterface {
    private \$arguments;

    // Dependencies


    // Services instances
    private \$std;
    private \$foo;

    public function __construct(MapInterface \$arguments)
    {
        \$this->arguments = \$arguments;

    }

    public function get(\$id): object
    {
        switch (\$id) {
            case 'bar':
                return \$this->buildFoo();
        }

        throw new NotFound(\$id);
    }

    public function has(\$id): bool
    {
        switch (\$id) {
            case 'bar':
                return true;
        }

        return false;
    }

        public function buildStd(): object
    {
        return \$this->std ?? \$this->std = new \stdClass(

);
    }

    public function buildFoo(): object
    {
        return \$this->foo ?? \$this->foo = new \stdClass(

);
    }




};
PHP;

        $this->assertSame($expected, (string) $this->dependency);
    }
}
