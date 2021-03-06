<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose\Definition\Service;

use Innmind\Compose\{
    Definition\Service\Constructors,
    Definition\Service\Constructor,
    Definition\Service\Constructor\Factory,
    Definition\Service\Constructor\ServiceFactory,
    Definition\Service\Constructor\Set,
    Definition\Service\Constructor\Stream,
    Definition\Service\Constructor\Construct,
    Definition\Service\Constructor\Merge,
    Definition\Service\Constructor\Map,
    Compilation\Service\Constructor as CompiledConstructor,
    Compilation\Service\Argument as CompiledArgument,
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
        $this->assertCount(7, Constructors::defaults());
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

            public function compile(CompiledArgument ...$arguments): CompiledConstructor
            {
            }

            public function __toString(): string
            {
                return '';
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
            ['$factory->make', ServiceFactory::class],
            ['set<int>', Set::class],
            ['map<int, int>', Map::class],
            ['stream<int>', Stream::class],
            ['merge', Merge::class],
        ];
    }
}
