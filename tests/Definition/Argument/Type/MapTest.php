<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose\Definition\Argument\Type;

use Innmind\Compose\{
    Definition\Argument\Type\Map,
    Definition\Argument\Type,
    Exception\ValueNotSupported
};
use Innmind\Immutable\{
    Map as M,
    Str
};
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
        $this->assertFalse($type->accepts('foo'));
        $this->assertFalse($type->accepts(new \stdClass));
    }

    public function testFromString()
    {
        $this->assertTrue(
            Map::fromString(Str::of('map<string, stdClass>'))->accepts(
                new M('string', 'stdClass')
            )
        );
        $this->assertTrue(
            Map::fromString(Str::of('map<string,stdClass>'))->accepts(
                new M('string', 'stdClass')
            )
        );
    }

    public function testThrowWhenNotSupported()
    {
        $this->expectException(ValueNotSupported::class);
        $this->expectExceptionMessage('foo');

        Map::fromString(Str::of('foo'));
    }
}
