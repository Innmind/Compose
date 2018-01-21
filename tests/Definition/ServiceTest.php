<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose\Definition;

use Innmind\Compose\{
    Definition\Service,
    Definition\Name,
    Definition\Service\Constructor,
    Definition\Service\Argument,
    Definition\Service\Arguments as Args,
    Exception\ServiceCannotDecorateMultipleServices,
    Exception\LogicException
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

    private $args;

    public function setUp()
    {
        $this->args = new Args;
    }

    public function testInterface()
    {
        $class = new class {};
        $class = get_class($class);

        $service = new Service(
            $name = new Name('foo'),
            $constructor = Constructor\Construct::fromString(Str::of($class)),
            $arg1 = $this->args->load('@decorated'),
            $arg2 = $this->args->load('$bar')
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
                    $this->args->load('@decorated'),
                    $this->args->load('@decorated')
                );
            });
    }

    public function testThrowWhenTryingToDecorateAServiceWhenNotIntendedTo()
    {
        $service = new Service(
            new Name('foo'),
            Constructor\Construct::fromString(Str::of('stdClass'))
        );

        $this->expectException(LogicException::class);

        $service->decorate(new Name('bar'));
    }

    public function testDecorate()
    {
        $service = new Service(
            new Name('foo'),
            Constructor\Construct::fromString(Str::of('stdClass')),
            $this->args->load(42),
            $arg = $this->args->load('@decorated'),
            $this->args->load(42)
        );

        $service2 = $service->decorate(new Name('bar'));

        $this->assertInstanceOf(Service::class, $service2);
        $this->assertNotSame($service2, $service);
        $this->assertNotSame($service->name(), $service2->name());
        $this->assertSame('foo.'.md5('bar'), (string) $service2->name());
        $this->assertSame($arg, $service->arguments()->get(1));
        $this->assertInstanceOf(
            Argument\Reference::class,
            $service2->arguments()->get(1)
        );
    }

    public function testThrowWhenTryingToDecorateAServiceThatAlreadyDecorates()
    {
        $service = new Service(
            new Name('foo'),
            Constructor\Construct::fromString(Str::of('stdClass')),
            $this->args->load('@decorated')
        );

        $this->expectException(LogicException::class);

        $service
            ->decorate(new Name('bar'))
            ->decorate(new Name('baz'));
    }
}
