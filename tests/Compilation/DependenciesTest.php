<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose\Compilation;

use Innmind\Compose\{
    Compilation\Dependencies,
    Definition\Dependency,
    Definition\Name,
    Definition\Service,
    Definition\Service\Constructor\Construct,
    Services,
    Arguments,
    Dependencies as Deps
};
use Innmind\Immutable\Str;
use PHPUnit\Framework\TestCase;

class DependenciesTest extends TestCase
{
    private $dependencies;

    public function setUp()
    {
        $this->dependencies = new Dependencies(
            new Dependency(
                new Name('foo'),
                new Services(
                    new Arguments,
                    new Deps,
                    new Service(
                        new Name('std'),
                        Construct::fromString(Str::of('stdClass'))
                    ),
                    (new Service(
                        new Name('foo'),
                        Construct::fromString(Str::of('stdClass'))
                    ))->exposeAs(new Name('bar'))
                )
            ),
            new Dependency(
                new Name('bar'),
                new Services(
                    new Arguments,
                    new Deps
                )
            )
        );
    }

    public function testProperties()
    {
        $expected = <<<PHP
    private \$foo;
    private \$bar;
PHP;

        $this->assertSame($expected, $this->dependencies->properties());
    }

    public function testExposed()
    {
        $expected = <<<PHP
    private function buildFooBar()
    {
        return \$this->foo->get('bar');
    }


PHP;

        $this->assertSame($expected, $this->dependencies->exposed());
    }

    public function testStringCast()
    {
        $expected = "        \$arguments = (new \Innmind\Immutable\Map('string', 'mixed'));
        \$this->foo = new class(\$arguments) implements ContainerInterface {
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
        \$arguments = (new \Innmind\Immutable\Map('string', 'mixed'));
        \$this->bar = new class(\$arguments) implements ContainerInterface {
    private \$arguments;

    // Dependencies


    // Services instances


    public function __construct(MapInterface \$arguments)
    {
        \$this->arguments = \$arguments;

    }

    public function get(\$id): object
    {
        switch (\$id) {

        }

        throw new NotFound(\$id);
    }

    public function has(\$id): bool
    {
        switch (\$id) {

                return true;
        }

        return false;
    }

".'    '."




};";

        $this->assertEquals($expected, (string) $this->dependencies);
    }
}
