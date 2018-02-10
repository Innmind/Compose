<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose;

use Innmind\Compose\{
    Compile,
    Container,
    Loader\Yaml,
    Exception\NotFound,
    Lazy\Map as LazyMap
};
use Innmind\Url\Path;
use Innmind\Immutable\Map;
use Psr\Container\ContainerInterface;
use PHPUnit\Framework\TestCase;
use Fixture\Innmind\Compose\ServiceFixture;

class CompileTest extends TestCase
{
    public function testInvokation()
    {
        $cache = '/tmp/'.md5('fixtures/container/full.yml').'.php';
        @unlink($cache);
        $compile = new Compile(new Path('/tmp/'));

        $container = $compile(
            new Yaml,
            new Path('fixtures/container/full.yml'),
            (new Map('string', 'mixed'))
                ->put('first', 42)
        );

        $this->assertTrue(file_exists($cache));
        $this->assertInstanceOf(ContainerInterface::class, $container);
        $this->assertNotInstanceOf(Container::class, $container);
        $this->assertTrue($container->has('foo'));
        $this->assertTrue($container->has('baz'));
        $this->assertFalse($container->has('inner.bar'));
        $this->assertInstanceOf(ServiceFixture::class, $container->get('foo'));
        $this->assertSame($container->get('foo'), $container->get('foo'));
        $this->assertInstanceOf(LazyMap::class, $container->get('map'));
        $this->assertSame(
            [$container->get('foo'), $container->get('baz')],
            $container->get('map')->values()->toPrimitive()
        );
        $this->assertSame('high|milieu|low|milieu|high', $container->get('stack')());

        $this->expectException(NotFound::class);
        $this->expectExceptionMessage('inner.bar');

        $container->get('inner.bar');
    }
}
