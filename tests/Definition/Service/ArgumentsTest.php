<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose\Definition\Service;

use Innmind\Compose\{
    Definition\Service\Arguments,
    Definition\Service\Argument,
    Definition\Service\Argument\Decorate,
    Definition\Service\Argument\Primitive,
    Definition\Service\Argument\Reference,
    Definition\Service\Argument\Unwind,
    Definitions,
    Exception\ValueNotSupported
};
use Innmind\Immutable\StreamInterface;
use PHPUnit\Framework\TestCase;

class ArgumentsTest extends TestCase
{
    public function testDefaults()
    {
        $this->assertInstanceOf(StreamInterface::class, Arguments::defaults());
        $this->assertSame('string', (string) Arguments::defaults()->type());
        $this->assertCount(4, Arguments::defaults());
        $this->assertSame(Arguments::defaults(), Arguments::defaults());
    }

    /**
     * @dataProvider defaults
     */
    public function testLoadFromDefaults($string, $expected)
    {
        $arguments = new Arguments;

        $type = $arguments->load($string);

        $this->assertInstanceOf(Argument::class, $type);
        $this->assertInstanceOf($expected, $type);
    }

    public function testLoadOnlyFromSpecifiedArguments()
    {
        $type = new class implements Argument {
            public static function fromValue($value): Argument
            {
                if ($value !== 'foo') {
                    throw new ValueNotSupported($value);
                }

                return new self;
            }

            public function resolve(
                StreamInterface $built,
                Definitions $definitions
            ): StreamInterface {
                return $built;
            }
        };
        $class = get_class($type);

        $arguments = new Arguments($class);

        $this->assertInstanceOf($class, $arguments->load('foo'));

        $this->expectException(ValueNotSupported::class);
        $this->expectExceptionMessage('\'int\'');

        $arguments->load('int');
    }

    public function defaults(): array
    {
        return [
            ['$foo', Reference::class],
            ['...$foo', Unwind::class],
            ['@decorated', Decorate::class],
            ['foo', Primitive::class],
            [[1, 2, 3], Primitive::class],
        ];
    }
}
