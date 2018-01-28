<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose;

use Innmind\Compose\{
    Lazy,
    Definitions,
    Arguments,
    Definition\Name,
    Definition\Service,
    Definition\Service\Constructor\Construct,
    Exception\ReferenceNotFound
};
use Innmind\Immutable\Str;
use PHPUnit\Framework\TestCase;

class LazyTest extends TestCase
{
    public function testThrowWhenReferenceNotFound()
    {
        $this->expectException(ReferenceNotFound::class);
        $this->expectExceptionMessage('foo');

        new Lazy(
            new Name('foo'),
            new Definitions(
                new Arguments
            )
        );
    }

    public function testLoad()
    {
        $lazy = new Lazy(
            new Name('foo'),
            $definitions = new Definitions(
                new Arguments,
                new Service(
                    new Name('foo'),
                    Construct::fromString(Str::of('stdClass'))
                )
            )
        );

        $service = $lazy->load();

        $this->assertInstanceOf('stdClass', $service);
        $this->assertSame($service, $definitions->build(new Name('foo')));
    }
}
