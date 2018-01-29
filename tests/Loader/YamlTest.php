<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose\Loader;

use Innmind\Compose\{
    Loader\Yaml,
    Loader,
    Definition\Name,
    Services
};
use Innmind\Url\Path;
use Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;
use Fixture\Innmind\Compose\ServiceFixture;

class YamlTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Loader::class,
            new Yaml
        );
    }

    public function testLoadFullContainer()
    {
        $load = new Yaml;

        $services = $load(new Path('fixtures/container/full.yml'));

        $this->assertInstanceOf(Services::class, $services);

        $services = $services->inject(Map::of(
            'string',
            'mixed',
            ['first'],
            [42]
        ));

        $foo = $services->build(new Name('foo'));
        $baz = $services->build(new Name('baz'));

        $this->assertInstanceOf(ServiceFixture::class, $foo);
        $this->assertInstanceOf(ServiceFixture::class, $baz);
        $this->assertSame($foo->second, $baz->second);
    }
}
