<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose\Definition\Argument\Type;

use Innmind\Compose\Definition\Argument\{
    Type\Instance,
    Type
};
use PHPUnit\Framework\TestCase;

class InstanceTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(Type::class, new Instance('foo'));
    }

    public function testAccepts()
    {
        $type = new Instance('stdClass');

        $this->assertTrue($type->accepts(new \stdClass));
        $this->assertFalse($type->accepts(new class{}));
    }
}
