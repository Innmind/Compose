<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose\Loader\PathResolver;

use Innmind\Compose\{
    Loader\PathResolver\Delegate,
    Loader\PathResolver,
    Exception\ValueNotSupported
};
use Innmind\Url\Path;
use PHPUnit\Framework\TestCase;

class DelegateTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(PathResolver::class, new Delegate);
    }

    public function testDelegate()
    {
        $resolve = new Delegate(
            $mock1 = $this->createMock(PathResolver::class),
            $mock2 = $this->createMock(PathResolver::class),
            $mock3 = $this->createMock(PathResolver::class)
        );
        $from = new Path('foo');
        $to = new Path('bar');
        $mock1
            ->expects($this->once())
            ->method('__invoke')
            ->with($from, $to)
            ->will($this->throwException(new ValueNotSupported));
        $mock2
            ->expects($this->once())
            ->method('__invoke')
            ->with($from, $to)
            ->willReturn($expected = new Path('baz'));
        $mock3
            ->expects($this->never())
            ->method('__invoke');

        $path = $resolve($from, $to);

        $this->assertSame($expected, $path);
    }

    public function testThrowWhenNoResolverCanDoItsJob()
    {
        $resolve = new Delegate(
            $mock = $this->createMock(PathResolver::class)
        );
        $from = new Path('foo');
        $to = new Path('bar');
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->with($from, $to)
            ->will($this->throwException(new ValueNotSupported));

        $this->expectException(ValueNotSupported::class);
        $this->expectExceptionMessage('bar');

        $resolve($from, $to);
    }
}
