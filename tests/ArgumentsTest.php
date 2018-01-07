<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose;

use Innmind\Compose\{
    Arguments,
    Definition\Argument,
    Definition\Name,
    Definition\Argument\Type\Primitive,
    Exception\MissingArgument,
    Exception\InvalidArgument,
    Exception\ArgumentNotProvided
};
use Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;

class ArgumentsTest extends TestCase
{
    public function testBind()
    {
        $arguments = new Arguments(
            new Argument(
                new Name('foo'),
                new Primitive('int')
            ),
            (new Argument(
                new Name('bar'),
                new Primitive('string')
            ))->makeOptional(),
            (new Argument(
                new Name('baz'),
                new Primitive('string')
            ))->defaultsTo(new Name('foobar'))
        );

        $arguments2 = $arguments->bind(Map::of(
            'string',
            'mixed',
            ['foo', 'bar'],
            [42, '42']
        ));

        $this->assertInstanceOf(Arguments::class, $arguments2);
        $this->assertNotSame($arguments, $arguments2);

        $this->assertInstanceOf(
            Arguments::class,
            $arguments->bind(Map::of(
                'string',
                'mixed',
                ['foo'],
                [42]
            ))
        );
    }

    public function testThrowWhenInvalidMap()
    {
        $arguments = new Arguments;

        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 1 must be of type MapInterface<string, mixed>');

        $arguments->bind(Map::of(
            'mixed',
            'mixed',
            [],
            []
        ));
    }

    public function testThrowWhenRequiredArgumentIsNotProvided()
    {
        $arguments = new Arguments(
            new Argument(
                new Name('foo'),
                new Primitive('int')
            )
        );

        $this->expectException(MissingArgument::class);
        $this->expectExceptionMessage('foo');

        $arguments->bind(new Map('string', 'mixed'));
    }

    public function testThrowWhenProvidedArgumentIsNotOfExpectedType()
    {
        $arguments = new Arguments(
            new Argument(
                new Name('foo'),
                new Primitive('int')
            )
        );

        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('foo');

        $arguments->bind(Map::of(
            'string',
            'mixed',
            ['foo'],
            ['42']
        ));
    }

    public function testGet()
    {
        $arguments = new Arguments(
            new Argument(
                new Name('foo'),
                new Primitive('int')
            )
        );
        $arguments = $arguments->bind(Map::of(
            'string',
            'mixed',
            ['foo'],
            [42]
        ));

        $this->assertSame(42, $arguments->get(new Name('foo')));
    }

    /**
     * @dataProvider arguments
     */
    public function testThrowWhenGettingAnArgumentNotProvided(Argument $argument)
    {
        $arguments = new Arguments($argument);
        $arguments = $arguments->bind(Map::of(
            'string',
            'mixed',
            [],
            []
        ));

        try {
            $arguments->get(new Name('foo'));

            $this->fail('it should throw');
        } catch (ArgumentNotProvided $e) {
            $this->assertSame($argument, $e->argument());
        }
    }

    public function arguments(): array
    {
        return [
            [
                (new Argument(
                    new Name('foo'),
                    new Primitive('int')
                ))->makeOptional(),
            ],
            [
                (new Argument(
                    new Name('foo'),
                    new Primitive('int')
                ))->defaultsTo(new Name('foobar'))
            ],
        ];
    }
}
