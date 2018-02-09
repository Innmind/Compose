<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose\Definition\Service\Constructor;

use Innmind\Compose\{
    Definition\Service\Constructor\Construct,
    Definition\Service\Constructor,
    Definition\Name,
    Definition\Service,
    Lazy,
    Services,
    Arguments,
    Dependencies
};
use Innmind\Immutable\Str;
use PHPUnit\Framework\TestCase;
use Fixture\Innmind\Compose\ServiceFixture;

class ConstructTest extends TestCase
{
    public function testConstructViaConstruct()
    {
        $construct = Construct::fromString(Str::of(ServiceFixture::class));

        $this->assertInstanceOf(Constructor::class, $construct);
        $this->assertSame(ServiceFixture::class, (string) $construct);

        $instance = $construct(1, new \stdClass);

        $this->assertInstanceOf(ServiceFixture::class, $instance);
        $this->assertSame(1, $instance->first);
    }

    public function testLoadLazyService()
    {
        $construct = Construct::fromString(Str::of(ServiceFixture::class));

        $instance = $construct(
            1,
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

        $this->assertInstanceOf(ServiceFixture::class, $instance);
    }
}
