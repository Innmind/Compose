<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose\Loader\PathResolver;

use Innmind\Compose\Loader\{
    PathResolver\Relative,
    PathResolver
};
use Innmind\Url\{
    PathInterface,
    Path
};
use PHPUnit\Framework\TestCase;

class RelativeTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(PathResolver::class, new Relative);
    }

    /**
     * @dataProvider cases
     */
    public function testInvokation($from, $to, $expected)
    {
        $resolve = new Relative;

        $path = $resolve(new Path($from), new Path($to));

        $this->assertInstanceOf(PathInterface::class, $path);
        $this->assertSame($expected, (string) $path);
    }

    public function cases(): array
    {
        return [
            [
                'fixtures/container/full.yml',
                'dep.yml',
                'fixtures/container/dep.yml',
            ],
            [
                'fixtures/container/full.yml',
                './dep.yml',
                'fixtures/container/dep.yml',
            ],
            [
                'fixtures/container/full.yml',
                '../dep.yml',
                'fixtures/dep.yml',
            ],
            [
                'fixtures/container/full.yml',
                '/dep.yml',
                '/dep.yml',
            ],
        ];
    }
}
