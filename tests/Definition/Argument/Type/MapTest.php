<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose\Definition\Argument\Type;

use Innmind\Compose\Definition\Argument\{
    Type\Map,
    Type
};
use Innmind\Immutable\Map as M;
use PHPUnit\Framework\TestCase;

class MapTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(Type::class, new Map('foo', 'bar'));
    }

    public function testAccepts()
    {
        $type = new Map('string', 'stdClass');

        $this->assertTrue($type->accepts(new M('string', 'stdClass')));
        $this->assertFalse($type->accepts(new M('foo', 'stdClass')));
        $this->assertFalse($type->accepts(new M('string', 'bar')));
        $this->assertFalse($type->accepts(new M('foo', 'bar')));
    }
}
