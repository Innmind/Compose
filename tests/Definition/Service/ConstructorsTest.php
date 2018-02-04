<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose\Definition\Service;

use Innmind\Compose\{
    Definition\Service\Constructors,
    Definition\Service\Constructor,
    Definition\Service\Constructor\Factory,
    Definition\Service\Constructor\Set,
    Definition\Service\Constructor\Stream,
    Definition\Service\Constructor\Construct,
    Definition\Service\Constructor\Merge,
    Exception\ValueNotSupported
};
use Innmind\Immutable\{
    StreamInterface,
    Str
};
use PHPUnit\Framework\TestCase;

class ConstructorsTest extends TestCase
{
    public function testDefaults()
    {
        $this->assertInstanceOf(StreamInterface::class, Constructors::defaults());
        $this->assertSame('string', (string) Constructors::defaults()->type());
        $this->assertCount(6, Constructors::defaults());
        $this->assertSame(Constructors::defaults(), Constructors::defaults());
    }

    /**
     * @dataProvider defaults
     */
    public function testLoadFromDefaults($string, $expected)
    {
        $constructors = new Constructors;

        $type = $constructors->load(Str::of($string));

        $this->assertInstanceOf(Constructor::class, $type);
        $this->assertInstanceOf($expected, $type);
    }

    public function testLoadOnlyFromSpecifiedConstructors()
    {
        $type = new class implements Constructor {
            public static function fromString(Str $value): Constructor
            {
                if ((string) $value !== 'foo') {
                    throw new ValueNotSupported((string) $value);
                }

                return new self;
            }

            public function __invoke(...$arguments): object
            {
            }
        };
        $class = get_class($type);

        $constructors = new Constructors($class);

        $this->assertInstanceOf($class, $constructors->load(Str::of('foo')));

        $this->expectException(ValueNotSupported::class);
        $this->expectExceptionMessage('\'stdClass\'');

        $constructors->load(Str::of('stdClass'));
    }

    public function defaults(): array
    {
        return [
            [ServiceFixture::class, Construct::class],
            [ServiceFixture::class.'::make', Factory::class],
            ['set<int>', Set::class],
            ['stream<int>', Stream::class],
            ['merge', Merge::class],
        ];
    }
}
