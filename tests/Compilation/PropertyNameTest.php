<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose\Compilation;

use Innmind\Compose\{
    Compilation\PropertyName,
    Definition\Name
};
use PHPUnit\Framework\TestCase;

class PropertyNameTest extends TestCase
{
    public function testStringCast()
    {
        $this->assertSame(
            'fooBarBaz',
            (string) new PropertyName(new Name('Foo.barBaz'))
        );
    }
}
