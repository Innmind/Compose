<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose\Loader;

use Innmind\Compose\{
    Loader\Yaml,
    Loader,
    Definition\Name
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

        $definitions = $load(new Path('fixtures/container/full.yml'));

        $definitions = $definitions->inject(Map::of(
            'string',
            'mixed',
            ['first'],
            [42]
        ));

        $foo = $definitions->build(new Name('foo'));
        $baz = $definitions->build(new Name('baz'));

        $this->assertInstanceOf(ServiceFixture::class, $foo);
        $this->assertInstanceOf(ServiceFixture::class, $baz);
        $this->assertSame($foo->second, $baz->second);
    }
}
