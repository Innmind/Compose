<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose\Loader\PathResolver;

use Innmind\Compose\{
    Loader\PathResolver\Composer,
    Loader\PathResolver,
    Exception\ValueNotSupported
};
use Innmind\Url\{
    PathInterface,
    Path
};
use PHPUnit\Framework\TestCase;

class ComposerTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(PathResolver::class, new Composer);
    }

    public function testInvokation()
    {
        $resolve = new Composer;

        $path = $resolve(
            new Path('some/relative/container.yml'),
            new Path('@user/package/path/to/container.yml')
        );

        $this->assertInstanceOf(PathInterface::class, $path);
        $this->assertSame(
            getcwd().'/vendor/user/package/path/to/container.yml',
            (string) $path
        );
    }

    public function testThrowWhenTargetFileDoesntRespectFormat()
    {
        $this->expectException(ValueNotSupported::class);
        $this->expectExceptionMessage('./relative/container.yml');

        (new Composer)(
            new Path('local.yml'),
            new Path('./relative/container.yml')
        );
    }
}
