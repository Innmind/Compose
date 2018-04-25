<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose\Definition\Service\Constructor;

use Innmind\Compose\{
    Definition\Service\Constructor\ServiceFactory,
    Definition\Service\Constructor\Construct,
    Definition\Service\Constructor,
    Definition\Service,
    Definition\Name,
    Services,
    Arguments,
    Dependencies,
    Lazy,
    Exception\ValueNotSupported,
    Compilation\Service\Constructor\ServiceFactory as CompiledServiceFactory,
    Compilation\Service\Argument,
};
use Innmind\Immutable\Str;
use PHPUnit\Framework\TestCase;
use Fixture\Innmind\Compose\ServiceFixture;

class ServiceFactoryTest extends TestCase
{
    public function testConstruct()
    {
        $construct = ServiceFactory::fromString(Str::of('$factory->make'));

        $this->assertInstanceOf(Constructor::class, $construct);
        $this->assertSame('$factory->make', (string) $construct);

        $factory = new class {
            public function make($std)
            {
                return $std;
            }
        };

        $instance = $construct($factory, $expected = new \stdClass);

        $this->assertSame($expected, $instance);
    }

    public function testThrowWhenNotOfExpectedFormat()
    {
        $this->expectException(ValueNotSupported::class);
        $this->expectExceptionMessage('$factory->');

        ServiceFactory::fromString(Str::of('$factory->'));
    }

    public function testLoadLazyService()
    {
        $construct = ServiceFactory::fromString(Str::of('$factory->make'));

        $instance = $construct(
            new class {
                public function make($std)
                {
                    return $std;
                }
            },
            Lazy::service(
                new Name('foo'),
                new Services(
                    new Arguments,
                    new Dependencies,
                    new Service(
                        new Name('foo'),
                        Construct::fromString(Str::of('stdClass'))
                    )
                )
            )
        );

        $this->assertInstanceOf('stdClass', $instance);
    }

    public function testCompile()
    {
        $this->assertInstanceOf(
            CompiledServiceFactory::class,
            ServiceFactory::fromString(Str::of('$factory->make'))->compile(
                $this->createMock(Argument::class),
                $this->createMock(Argument::class)
            )
        );
    }
}
