<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose\Definition;

use Innmind\Compose\{
    Definition\Name,
    Exception\NameMustContainAtLeastACharacter,
    Exception\NameNotNamespaced
};
use PHPUnit\Framework\TestCase;
use Eris\{
    TestTrait,
    Generator
};

class NameTest extends TestCase
{
    use TestTrait;

    public function testAcceptNonEmptyName()
    {
        $this
            ->forAll(Generator\string())
            ->when(static function(string $string): bool {
                return strlen($string) > 0;
            })
            ->then(function(string $string): void {
                $this->assertSame($string, (string) new Name($string));
            });
    }
    public function testAdd()
    {
        $this
            ->forAll(
                Generator\string(),
                Generator\string()
            )
            ->when(static function(string $root, string $add): bool {
                return strlen($root) > 0 && strlen($add) > 0;
            })
            ->then(function(string $root, string $add): void {
                $this->assertSame($root.'.'.$add, (string) (new Name($root))->add(new Name($add)));
            });
    }

    public function testThrowOnEmptyName()
    {
        $this->expectException(NameMustContainAtLeastACharacter::class);

        new Name('');
    }

    public function testRoot()
    {
        $name = new Name('foo.bar.baz');

        $root = $name->root();

        $this->assertInstanceOf(Name::class, $root);
        $this->assertNotSame($name, $root);
        $this->assertSame('foo', (string) $root);
        $this->assertSame('foo.bar.baz', (string) $name);
    }

    public function testThrowWhenNoRoot()
    {
        $this->expectException(NameNotNamespaced::class);
        $this->expectExceptionMessage('foo');

        (new Name('foo'))->root();
    }

    public function testWithoutRoot()
    {
        $name = new Name('foo.bar.baz');

        $name2 = $name->withoutRoot();

        $this->assertInstanceOf(Name::class, $name2);
        $this->assertNotSame($name, $name2);
        $this->assertSame('foo.bar.baz', (string) $name);
        $this->assertSame('bar.baz', (string) $name2);
    }

    public function testThrowWhenTryingToRemoveARootThatDoesntExist()
    {
        $this->expectException(NameNotNamespaced::class);
        $this->expectExceptionMessage('foo');

        (new Name('foo'))->withoutRoot();
    }

    public function testEquals()
    {
        $this->assertTrue((new Name('foo.bar'))->equals(new Name('foo.bar')));
        $this->assertFalse((new Name('foo.bar'))->equals(new Name('bar.foo')));
    }
}
