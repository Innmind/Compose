<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose\Definition\Service\Constructor;

use Innmind\Compose\{
    Definition\Service\Constructor\Factory,
    Definition\Service\Constructor\Construct,
    Definition\Service\Constructor,
    Definition\Service,
    Definition\Name,
    Services,
    Arguments,
    Lazy,
    Exception\ValueNotSupported
};
use Innmind\Immutable\Str;
use PHPUnit\Framework\TestCase;
use Fixture\Innmind\Compose\ServiceFixture;

class FactoryTest extends TestCase
{
    public function testConstruct()
    {
        $construct = Factory::fromString(Str::of(ServiceFixture::class.'::make'));

        $this->assertInstanceOf(Constructor::class, $construct);

        $instance = $construct(1, new \stdClass);

        $this->assertInstanceOf(ServiceFixture::class, $instance);
        $this->assertSame(1, $instance->first);
    }

    public function testThrowWhenNotOfExpectedFormat()
    {
        $this->expectException(ValueNotSupported::class);
        $this->expectExceptionMessage(ServiceFixture::class);

        Factory::fromString(Str::of(ServiceFixture::class));
    }

    public function testLoadLazyService()
    {
        $construct = Factory::fromString(Str::of(ServiceFixture::class.'::make'));

        $instance = $construct(
            1,
            new Lazy(
                new Name('foo'),
                new Services(
                    new Arguments,
                    new Service(
                        new Name('foo'),
                        Construct::fromString(Str::of('stdClass'))
                    )
                )
            )
        );

        $this->assertInstanceOf(ServiceFixture::class, $instance);
    }
}
