<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose;

use Innmind\Compose\{
    Definitions,
    Arguments,
    Definition\Argument,
    Definition\Argument\Name as ArgName,
    Definition\Argument\Type\Primitive,
    Definition\Service,
    Definition\Service\Name,
    Definition\Service\Constructor
};
use Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;

class DefinitionsTest extends TestCase
{
    public function testInterface()
    {
        $definitions = new Definitions(
            $arguments = new Arguments,
            $service = new Service(
                new Name('foo'),
                new Constructor('stdClass')
            )
        );

        $this->assertSame($arguments, $definitions->arguments());
        $this->assertTrue($definitions->has(new Name('foo')));
        $this->assertFalse($definitions->has(new Name('bar')));
        $this->assertSame($service, $definitions->get(new Name('foo')));
    }

    public function testInject()
    {
        $definitions = new Definitions(
            $arguments = new Arguments(
                new Argument(
                    new ArgName('baz'),
                    new Primitive('string')
                )
            ),
            $service = new Service(
                new Name('foo'),
                new Constructor('stdClass')
            )
        );

        $definitions2 = $definitions->inject(Map::of(
            'string',
            'mixed',
            ['baz'],
            ['42']
        ));

        $this->assertInstanceOf(Definitions::class, $definitions2);
        $this->assertNotSame($definitions, $definitions2);
        $this->assertNotSame($definitions->arguments(), $definitions2->arguments());
        $this->assertSame($arguments, $definitions->arguments());
        $this->assertNotSame($arguments, $definitions2->arguments());
        $this->assertSame('42', $definitions2->arguments()->get(new ArgName('baz')));
    }
}
