<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose\Definition;

use Innmind\Compose\{
    Definition\Service,
    Definition\Name,
    Definition\Service\Constructor,
    Definition\Service\Argument,
    Exception\ServiceCannotDecorateMultipleServices
};
use Innmind\Immutable\{
    StreamInterface,
    Str
};
use PHPUnit\Framework\TestCase;
use Eris\{
    TestTrait,
    Generator
};

class ServiceTest extends TestCase
{
    use TestTrait;

    public function testInterface()
    {
        $class = new class {};
        $class = get_class($class);

        $service = new Service(
            $name = new Name('foo'),
            $constructor = Constructor\Construct::fromString(Str::of($class)),
            $arg1 = Argument\Decorate::fromValue('@decorated'),
            $arg2 = Argument\Reference::fromValue('$bar')
        );

        $this->assertSame($name, $service->name());
        $this->assertSame($constructor, $service->constructor());
        $this->assertInstanceOf(StreamInterface::class, $service->arguments());
        $this->assertCount(2, $service->arguments());
        $this->assertSame([$arg1, $arg2], $service->arguments()->toPrimitive());
    }

    public function testExpose()
    {
        $class = new class {};
        $class = get_class($class);

        $service = new Service(
            $name = new Name('foo'),
            Constructor\Construct::fromString(Str::of($class))
        );

        $service2 = $service->exposeAs($expose = new Name('baz'));

        $this->assertInstanceOf(Service::class, $service2);
        $this->assertNotSame($service, $service2);
        $this->assertFalse($service->exposed());
        $this->assertTrue($service2->exposed());
        $this->assertSame($name, $service2->name());
        $this->assertSame($expose, $service2->exposedAs());
        $this->assertFalse($service->isExposedAs($expose));
        $this->assertTrue($service2->isExposedAs($expose));
        $this->assertFalse($service2->isExposedAs(new Name('unknown')));
    }

    public function testThrowWhenTryingToDecorateMultipleService()
    {
        $this
            ->forAll(Generator\string())
            ->when(static function(string $string): bool {
                return strlen($string) > 0;
            })
            ->then(function(string $string): void {
                $class = new class {};
                $class = get_class($class);

                $this->expectException(ServiceCannotDecorateMultipleServices::class);
                $this->expectExceptionMessage('foo');

                new Service(
                    new Name('foo'),
                    Constructor\Construct::fromString(Str::of($class)),
                    Argument\Decorate::fromValue('@decorated'),
                    Argument\Decorate::fromValue('@decorated')
                );
            });
    }
}
