<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose\Definition\Argument\Type;

use Innmind\Compose\{
    Definition\Argument\Type\Sequence,
    Definition\Argument\Type,
    Exception\ValueNotSupported
};
use Innmind\Immutable\{
    Sequence as S,
    Str
};
use PHPUnit\Framework\TestCase;

class SequenceTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(Type::class, new Sequence);
        $this->assertSame('sequence', (string) new Sequence);
    }

    public function testAccepts()
    {
        $type = new Sequence;

        $this->assertTrue($type->accepts(new S));
        $this->assertFalse($type->accepts([]));
    }

    public function testFromString()
    {
        $this->assertTrue(
            Sequence::fromString(Str::of('sequence'))->accepts(new S)
        );
    }

    public function testThrowWhenNotSupported()
    {
        $this->expectException(ValueNotSupported::class);
        $this->expectExceptionMessage('seq');

        Sequence::fromString(Str::of('seq'));
    }
}
