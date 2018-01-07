<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose\Definition\Argument\Type;

use Innmind\Compose\Definition\Argument\{
    Type\Stream,
    Type
};
use Innmind\Immutable\Stream as S;
use PHPUnit\Framework\TestCase;

class StreamTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(Type::class, new Stream('foo'));
    }

    public function testAccepts()
    {
        $type = new Stream('stdClass');

        $this->assertTrue($type->accepts(new S('stdClass')));
        $this->assertFalse($type->accepts(new S('foo')));
    }
}
