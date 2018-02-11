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

    public function testCachedContainerIsNotRecompiled()
    {
        $cache = '/tmp/'.md5('fixtures/container/full.yml').'.php';
        @unlink($cache);
        $compile = new Compile(new Path('/tmp/'));

        $compile(
            new Yaml,
            new Path('fixtures/container/full.yml'),
            (new Map('string', 'mixed'))
                ->put('first', 42)
        );
        file_put_contents($cache, <<<PHP
<?php
return new class implements \Psr\Container\ContainerInterface {
    public function get(\$id)
    {
        throw new \Exception('not overridden');
    }

    public function has(\$id)
    {
        return null;
    }
};
PHP
        );
        //sleep otherwise the modify time of the definition will be the same
        sleep(2);

        $container = $compile(
            new Yaml,
            new Path('fixtures/container/full.yml'),
            (new Map('string', 'mixed'))
                ->put('first', 42)
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('not overridden');

        $container->get('foo');
    }

    public function testCachedContainerIsRecompiled()
    {
        $cache = '/tmp/'.md5('fixtures/container/full.yml').'.php';
        @unlink($cache);
        $compile = Compile::onChange(new Path('/tmp/'));

        $compile(
            new Yaml,
            new Path('fixtures/container/full.yml'),
            (new Map('string', 'mixed'))
                ->put('first', 42)
        );
        file_put_contents($cache, <<<PHP
<?php
return new class implements \Psr\Container\ContainerInterface {
    public function get(\$id)
    {
        throw new \Exception('not overridden');
    }

    public function has(\$id)
    {
        return null;
    }
};
PHP
        );
        //sleep otherwise the modify time of the definition will be the same
        sleep(2);
        file_put_contents(
            'fixtures/container/full.yml',
            file_get_contents('fixtures/container/full.yml')."\n"
        );

        $container = $compile(
            new Yaml,
            new Path('fixtures/container/full.yml'),
            (new Map('string', 'mixed'))
                ->put('first', 42)
        );

        $this->assertTrue($container->has('foo'));
    }
}
