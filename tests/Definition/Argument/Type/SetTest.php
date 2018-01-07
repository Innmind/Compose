<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose\Definition\Argument\Type;

use Innmind\Compose\Definition\Argument\{
    Type\Set,
    Type
};
use Innmind\Immutable\Set as S;
use PHPUnit\Framework\TestCase;

class SetTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(Type::class, new Set('foo'));
    }

    public function testAccepts()
    {
        $type = new Set('stdClass');

        $this->assertTrue($type->accepts(new S('stdClass')));
        $this->assertFalse($type->accepts(new S('foo')));
        $this->assertFalse($type->accepts(new \stdClass));
    }
}
