<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose\Definition\Argument\Type;

use Innmind\Compose\Definition\Argument\{
    Type\Sequence,
    Type
};
use Innmind\Immutable\Sequence as S;
use PHPUnit\Framework\TestCase;

class SequenceTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(Type::class, new Sequence);
    }

    public function testAccepts()
    {
        $type = new Sequence;

        $this->assertTrue($type->accepts(new S));
        $this->assertFalse($type->accepts([]));
    }
}
