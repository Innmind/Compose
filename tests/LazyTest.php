<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose;

use Innmind\Compose\{
    Lazy,
    Services,
    Arguments,
    Dependencies,
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

        Lazy::service(
            new Name('foo'),
            new Services(
                new Arguments,
                new Dependencies
            )
        );
    }

    public function testLoad()
    {
        $lazy = Lazy::service(
            new Name('foo'),
            $services = new Services(
                new Arguments,
                new Dependencies,
                new Service(
                    new Name('foo'),
                    Construct::fromString(Str::of('stdClass'))
                )
            )
        );

        $service = $lazy->load();

        $this->assertInstanceOf('stdClass', $service);
        $this->assertSame($service, $services->build(new Name('foo')));
    }

    public function testCallableIsNotCalledAtConstructTime()
    {
        $lazy = new Lazy(static function() {
            throw new \Exception;
        });

        $this->expectException(\Exception::class);

        $lazy->load();
    }
}
