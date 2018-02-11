<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose\Compilation;

use Innmind\Compose\{
    Compilation\Arguments,
    Definition\Argument,
    Definition\Argument\Type\Primitive,
    Definition\Name,
    Arguments as Container
};
use PHPUnit\Framework\TestCase;

class ArgumentsTest extends TestCase
{
    public function testStringCast()
    {
        $arguments = new Arguments(
            new Container(
                new Argument(
                    new Name('foo'),
                    new Primitive('int')
                ),
                new Argument(
                    new Name('bar'),
                    new Primitive('string')
                )
            )
        );

        $expected = "    public function buildFoo()
    {
        if (\$this->arguments->contains('foo')) {
            return \$this->arguments->get('foo');
        }

".'        '."
        throw new Innmind\Compose\Exception\LogicException('Missing argument \"foo\"');
    }

    public function buildBar()
    {
        if (\$this->arguments->contains('bar')) {
            return \$this->arguments->get('bar');
        }

".'        '."
        throw new Innmind\Compose\Exception\LogicException('Missing argument \"bar\"');
    }";

        $this->assertSame($expected, (string) $arguments);
    }
}
