<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose\Compilation;

use Innmind\Compose\{
    Compilation\MethodName,
    Definition\Name
};
use PHPUnit\Framework\TestCase;

class MethodNameTest extends TestCase
{
    public function testStringCast()
    {
        $this->assertSame(
            'buildFooBarBaz',
            (string) new MethodName(new Name('foo.barBaz'))
        );
    }
}
