<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose\Definition;

use Innmind\Compose\{
    Definition\Name,
    Exception\NameMustContainAtLeastACharacter
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

    public function testThrowOnEmptyName()
    {
        $this->expectException(NameMustContainAtLeastACharacter::class);

        new Name('');
    }
}
