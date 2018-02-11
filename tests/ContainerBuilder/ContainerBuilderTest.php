<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose\ContainerBuilder;

use Innmind\Compose\{
    ContainerBuilder\ContainerBuilder,
    ContainerBuilder as ContainerBuilderInterface,
    Container,
    Loader\Yaml,
    Definition\Argument\Types,
    Definition\Service\Arguments,
    Definition\Service\Constructors
};
use Innmind\Url\Path;
use Innmind\Immutable\{
    MapInterface,
    Map,
    SetInterface,
    StreamInterface
};
use PHPUnit\Framework\TestCase;
use Fixture\Innmind\Compose\{
    ServiceFixture,
    Stack\High
};

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

        $set = $container->get('set');
        $this->assertInstanceOf(SetInterface::class, $set);
        $this->assertSame('int', (string) $set->type());
        $this->assertCount(3, $set);
        $this->assertSame([1, 2, 42], $set->toPrimitive());

        $stream = $container->get('stream');
        $this->assertInstanceOf(StreamInterface::class, $stream);
        $this->assertSame('string', (string) $stream->type());
        $this->assertCount(3, $stream);
        $this->assertSame(['foo', 'bar', 'baz'], $stream->toPrimitive());

        $map = $container->get('map');
        $this->assertInstanceOf(MapInterface::class, $map);
        $this->assertSame('string', (string) $map->keyType());
        $this->assertSame(ServiceFixture::class, (string) $map->valueType());
        $this->assertCount(2, $map);
        $this->assertSame($service, $map->get('bar'));
        $this->assertSame($container->get('baz'), $map->get('baz'));

        $stack = $container->get('stack');
        $this->assertInstanceOf(High::class, $stack);
        $this->assertSame('high|milieu|low|milieu|high', $stack());
    }
}
