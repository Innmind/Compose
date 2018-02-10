<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose\Compilation\Dependency;

use Innmind\Compose\{
    Compilation\Dependency\Parameter,
    Definition\Name
};
use PHPUnit\Framework\TestCase;

class ParameterTest extends TestCase
{
    public function testRawCast()
    {
        $parameter = Parameter::raw(new Name('foo'), 42);

        $this->assertInstanceOf(Parameter::class, $parameter);
        $this->assertSame('->put(\'foo\', 42)', (string) $parameter);
    }

    public function testReferenceCast()
    {
        $parameter = Parameter::reference(new Name('foo'), new Name('bar'));

        $this->assertInstanceOf(Parameter::class, $parameter);
        $this->assertSame('->put(\'foo\', $this->buildBar())', (string) $parameter);
    }
}
