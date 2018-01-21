<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose\Definition\Service\Constructor;

use Innmind\Compose\Definition\Service\{
    Constructor\Construct,
    Constructor
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

        $instance = $construct(1, new \stdClass);

        $this->assertInstanceOf(ServiceFixture::class, $instance);
        $this->assertSame(1, $instance->first);
    }
}
