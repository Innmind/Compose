<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose\Compilation;

use Innmind\Compose\{
    Compilation\Argument,
    Definition\Argument as Definition,
    Definition\Argument\Type\Primitive,
    Definition\Name
};
use PHPUnit\Framework\TestCase;

class ArgumentTest extends TestCase
{
    public function testRequiredStringCast()
    {
        $argument = new Argument(
            new Definition(
                new Name('foo'),
                new Primitive('int')
            )
        );

        $expected = "    public function buildFoo()
    {
        if (\$this->arguments->contains('foo')) {
            return \$this->arguments->get('foo');
        }

".'        '."
        throw new Innmind\Compose\Exception\LogicException('Missing argument \"foo\"');
    }";

        $this->assertSame($expected, (string) $argument);
    }

    public function testOptionalStringCast()
    {
        $argument = new Argument(
            (new Definition(
                new Name('foo'),
                new Primitive('int')
            ))->makeOptional()
        );

        $expected = "    public function buildFoo()
    {
        if (\$this->arguments->contains('foo')) {
            return \$this->arguments->get('foo');
        }

".'        '."
        return null;
    }";

        $this->assertSame($expected, (string) $argument);
    }

    public function testDefaultsStringCast()
    {
        $argument = new Argument(
            (new Definition(
                new Name('foo'),
                new Primitive('int')
            ))->defaultsTo(new Name('baz'))
        );

        $expected = "    public function buildFoo()
    {
        if (\$this->arguments->contains('foo')) {
            return \$this->arguments->get('foo');
        }

        return \$this->buildBaz();
".'        '."
    }";

        $this->assertSame($expected, (string) $argument);
    }
}
