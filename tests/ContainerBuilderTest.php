<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose;

use Innmind\Compose\{
    ContainerBuilder,
    Container,
    Loader\Yaml,
    Definition\Argument\Types,
    Definition\Service\Arguments,
    Definition\Service\Constructors
};
use Innmind\Url\Path;
use Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;
use Fixture\Innmind\Compose\ServiceFixture;

class ContainerBuilderTest extends TestCase
{
    public function testInvokation()
    {
        $build = new ContainerBuilder(
            new Yaml(
                new Types,
                new Arguments,
                new Constructors
            )
        );

        $container = $build(
            new Path('fixtures/container/full.yml'),
            (new Map('string', 'mixed'))
                ->put('first', 42)
        );

        $this->assertInstanceOf(Container::class, $container);

        $service = $container->get('foo');
        $this->assertInstanceOf(ServiceFixture::class, $service);
        $this->assertSame(42, $service->first);
    }
}
