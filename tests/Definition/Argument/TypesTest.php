<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose\Definition\Argument;

use Innmind\Compose\{
    Definition\Argument\Types,
    Definition\Argument\Type,
    Definition\Argument\Type\Instance,
    Definition\Argument\Type\Map,
    Definition\Argument\Type\Primitive,
    Definition\Argument\Type\Sequence,
    Definition\Argument\Type\Set,
    Definition\Argument\Type\Stream,
    Exception\ValueNotSupported
};
use Innmind\Immutable\{
    StreamInterface,
    Str
};
use PHPUnit\Framework\TestCase;

class TypesTest extends TestCase
{
    public function testDefaults()
    {
        $this->assertInstanceOf(StreamInterface::class, Types::defaults());
        $this->assertSame('string', (string) Types::defaults()->type());
        $this->assertCount(6, Types::defaults());
        $this->assertSame(Types::defaults(), Types::defaults());
    }

    /**
     * @dataProvider defaults
     */
    public function testLoadFromDefaults($string, $expected)
    {
        $types = new Types;

        $type = $types->load(Str::of($string));

        $this->assertInstanceOf(Type::class, $type);
        $this->assertInstanceOf($expected, $type);
    }

    public function testLoadOnlyFromSpecifiedTypes()
    {
        $type = new class implements Type {
            public function accepts($value): bool
            {
                return true;
            }

            public static function fromString(Str $type): Type
            {
                if ((string) $type !== 'foo') {
                    throw new ValueNotSupported((string) $type);
                }

                return new self;
            }
        };
        $class = get_class($type);

        $types = new Types($class);

        $this->assertInstanceOf($class, $types->load(Str::of('foo')));

        $this->expectException(ValueNotSupported::class);
        $this->expectExceptionMessage('int');

        $types->load(Str::of('int'));
    }

    public function defaults(): array
    {
        return [
            ['stdClass', Instance::class],
            ['int', Primitive::class],
            ['map<foo, bar>', Map::class],
            ['sequence', Sequence::class],
            ['set<int>', Set::class],
            ['stream<int>', Stream::class],
        ];
    }
}
