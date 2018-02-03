<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose\Loader;

use Innmind\Compose\{
    Loader\Yaml,
    Loader,
    Definition\Name,
    Services,
    Exception\DomainException
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

        $this->assertFalse($services->has(new Name('depStack')));

        $services = $services->inject(Map::of(
            'string',
            'mixed',
            ['first'],
            [42]
        ));

        $foo = $services->build(new Name('foo'));
        $baz = $services->build(new Name('baz'));
        $fromDep = $services->build(new Name('fromDep'));
        $dep = $services->dependencies()->build(new Name('dep.fixture'));

        $this->assertInstanceOf(ServiceFixture::class, $foo);
        $this->assertInstanceOf(ServiceFixture::class, $baz);
        $this->assertInstanceOf(ServiceFixture::class, $fromDep);
        $this->assertInstanceOf(ServiceFixture::class, $dep);
        $this->assertSame($foo->second, $baz->second);
        $this->assertSame($foo->second, $fromDep->second);
        $this->assertSame($foo->second, $dep->second);
        $this->assertSame(24, $dep->first);
        $this->assertSame($dep, $fromDep->third[0]);
    }

    public function testThrowWhenInvalidServiceDefinition()
    {
        $this->expectException(DomainException::class);

        (new Yaml)(new Path('fixtures/container/invalidServiceStructure.yml'));
    }
}
