<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose\Definition\Argument;

use Innmind\Compose\{
    Definition\Argument\Name,
    Exception\NameMustBeAlphaNumeric
};
use PHPUnit\Framework\TestCase;
use Eris\{
    TestTrait,
    Generator
};

class NameTest extends TestCase
{
    use TestTrait;

    public function testAcceptAlphaNumericName()
    {
        $this->assertSame('FooBar90', (string) new Name('FooBar90'));
    }

    public function testThrowWhenNotAlphaNumeric()
    {
        $this
            ->forAll(Generator\string())
            ->when(static function(string $string): bool {
                return !preg_match('~^[a-zA-Z0-9]+$~', $string);
            })
            ->then(function(string $string): void {
                $this->expectException(NameMustBeAlphaNumeric::class);
                $this->expectExceptionMessage($string);

                new Name($string);
            });
    }

    public function testThrowOnEmptyName()
    {
        $this->expectException(NameMustBeAlphaNumeric::class);

        new Name('');
    }
}
