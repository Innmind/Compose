<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose\Compilation;

use Innmind\Compose\{
    Compilation\Services,
    Loader\Yaml
};
use Innmind\Url\Path;
use PHPunit\Framework\TestCase;

class ServicesTest extends TestCase
{
    public function testStringCast()
    {
        $services = new Services(
            (new Yaml)(new Path('fixtures/container/full.yml'))
        );

        $expected = "new class(\$arguments) implements ContainerInterface {
    private \$arguments;

    // Dependencies
    private \$dep;

    // Services instances
    private \$innerBar;
    private \$innerBaz;
    private \$innerFallbackForBar;
    private \$ints;
    private \$strings;
    private \$fixtures;
    private \$stackLow;
    private \$fromDep;
    private \$tunnel37c76f70a65dbde92256c277c7c52469;
    private \$fixtureStack;

    public function __construct(MapInterface \$arguments)
    {
        \$this->arguments = \$arguments;
        \$arguments = (new \Innmind\Immutable\Map('string', 'mixed'))
->put('first', 24)
->put('second', \$this->buildInnerFallbackForBar())
->put('middleText', 'milieu');
        \$this->dep = new class(\$arguments) implements ContainerInterface {
    private \$arguments;

    // Dependencies


    // Services instances
    private \$fixture;
    private \$stackLow;
    private \$stackMiddleF88f3bdd8425ff5c324984c9e21751a5;
    private \$depStack;

    public function __construct(MapInterface \$arguments)
    {
        \$this->arguments = \$arguments;

    }

    public function get(\$id): object
    {
        switch (\$id) {
            case 'fixture':
                return \$this->buildFixture();
        }

        throw new NotFound(\$id);
    }

    public function has(\$id): bool
    {
        switch (\$id) {
            case 'fixture':
                return true;
        }

        return false;
    }

        public function buildFixture(): object
    {
        return \$this->fixture ?? \$this->fixture = new \Fixture\Innmind\Compose\ServiceFixture(
\$this->buildFirst(),
\$this->buildSecond()
);
    }

    public function buildStackLow(): object
    {
        return \$this->stackLow ?? \$this->stackLow = new \Fixture\Innmind\Compose\Stack\Low(

);
    }

    public function buildStackMiddleF88f3bdd8425ff5c324984c9e21751a5(): object
    {
        return \$this->stackMiddleF88f3bdd8425ff5c324984c9e21751a5 ?? \$this->stackMiddleF88f3bdd8425ff5c324984c9e21751a5 = new \Fixture\Innmind\Compose\Stack\Middle(
\$this->buildStackLow(),
\$this->buildMiddleText()
);
    }

    public function buildDepStack(): object
    {
        return \$this->depStack ?? \$this->depStack = new \Fixture\Innmind\Compose\Stack\High(
\$this->buildStackMiddleF88f3bdd8425ff5c324984c9e21751a5()
);
    }

    public function buildFirst()
    {
        if (\$this->arguments->contains('first')) {
            return \$this->arguments->get('first');
        }

".'        '."
        throw new Innmind\Compose\Exception\LogicException('Missing argument \"first\"');
    }

    public function buildSecond()
    {
        if (\$this->arguments->contains('second')) {
            return \$this->arguments->get('second');
        }

".'        '."
        throw new Innmind\Compose\Exception\LogicException('Missing argument \"second\"');
    }

    public function buildMiddleText()
    {
        if (\$this->arguments->contains('middleText')) {
            return \$this->arguments->get('middleText');
        }

".'        '."
        throw new Innmind\Compose\Exception\LogicException('Missing argument \"middleText\"');
    }


};
    }

    public function get(\$id): object
    {
        switch (\$id) {
            case 'foo':
                return \$this->buildInnerBar();
            case 'baz':
                return \$this->buildInnerBaz();
            case 'set':
                return \$this->buildInts();
            case 'stream':
                return \$this->buildStrings();
            case 'map':
                return \$this->buildFixtures();
            case 'fromDep':
                return \$this->buildFromDep();
            case 'stack':
                return \$this->buildFixtureStack();
        }

        throw new NotFound(\$id);
    }

    public function has(\$id): bool
    {
        switch (\$id) {
            case 'foo':
            case 'baz':
            case 'set':
            case 'stream':
            case 'map':
            case 'fromDep':
            case 'stack':
                return true;
        }

        return false;
    }

        public function buildInnerBar(): object
    {
        return \$this->innerBar ?? \$this->innerBar = new \Fixture\Innmind\Compose\ServiceFixture(
\$this->buildFirst(),
\$this->buildSecond(),
\$this->buildThird()
);
    }

    public function buildInnerBaz(): object
    {
        return \$this->innerBaz ?? \$this->innerBaz = new \Fixture\Innmind\Compose\ServiceFixture(
\$this->buildFirst(),
\$this->buildInnerFallbackForBar()
);
    }

    public function buildInnerFallbackForBar(): object
    {
        return \$this->innerFallbackForBar ?? \$this->innerFallbackForBar = new \stdClass(

);
    }

    public function buildInts(): object
    {
        return \$this->ints ?? \$this->ints = \Innmind\Compose\Lazy\Set::of(
    'int',
    1,
2,
42
);
    }

    public function buildStrings(): object
    {
        return \$this->strings ?? \$this->strings = \Innmind\Compose\Lazy\Stream::of(
    'string',
    'foo',
'bar',
'baz'
);
    }

    public function buildFixtures(): object
    {
        return \$this->fixtures ?? \$this->fixtures = \Innmind\Compose\Lazy\Map::of(
    'string',
    'Fixture\Innmind\Compose\ServiceFixture',
    new \Innmind\Immutable\Pair(
    'bar',
    new \Innmind\Compose\Lazy(function() {
    return \$this->buildInnerBar();
})
),
new \Innmind\Immutable\Pair(
    'baz',
    new \Innmind\Compose\Lazy(function() {
    return \$this->buildInnerBaz();
})
)
);
    }

    public function buildStackLow(): object
    {
        return \$this->stackLow ?? \$this->stackLow = new \Fixture\Innmind\Compose\Stack\Low(

);
    }

    public function buildFromDep(): object
    {
        return \$this->fromDep ?? \$this->fromDep = new \Fixture\Innmind\Compose\ServiceFixture(
42,
\$this->buildSecond(),
\$this->buildDepFixture()
);
    }

    public function buildTunnel37c76f70a65dbde92256c277c7c52469(): object
    {
        return \$this->tunnel37c76f70a65dbde92256c277c7c52469 ?? \$this->tunnel37c76f70a65dbde92256c277c7c52469 = new \Fixture\Innmind\Compose\Stack\Middle(
\$this->buildStackLow(),
\$this->dep->buildMiddleText()
);
    }

    public function buildFixtureStack(): object
    {
        return \$this->fixtureStack ?? \$this->fixtureStack = new \Fixture\Innmind\Compose\Stack\High(
\$this->buildTunnel37c76f70a65dbde92256c277c7c52469()
);
    }

    public function buildFirst()
    {
        if (\$this->arguments->contains('first')) {
            return \$this->arguments->get('first');
        }

".'        '."
        throw new Innmind\Compose\Exception\LogicException('Missing argument \"first\"');
    }

    public function buildSecond()
    {
        if (\$this->arguments->contains('second')) {
            return \$this->arguments->get('second');
        }

        return \$this->buildInnerFallbackForBar();
".'        '."
    }

    public function buildThird()
    {
        if (\$this->arguments->contains('third')) {
            return \$this->arguments->get('third');
        }

".'        '."
        return null;
    }

    private function buildDepFixture()
    {
        return \$this->dep->get('fixture');
    }

    private function buildDepMiddle()
    {
        return \$this->dep->get('middle');
    }
};";

        $this->assertEquals($expected, (string) $services);
    }
}
