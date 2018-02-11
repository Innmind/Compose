<?php
declare(strict_types = 1);

namespace Innmind\Compose\Compilation\Dependency;

use Innmind\Compose\{
    Definition\Name,
    Compilation\MethodName
};

final class Parameter
{
    private $name;
    private $value;

    private function __construct(Name $name, string $value)
    {
        $this->name = $name;
        $this->value = $value;
    }

    public static function raw(Name $name, $primitive): self
    {
        return new self(
            $name,
            var_export($primitive, true)
        );
    }

    public static function reference(Name $name, Name $reference): self
    {
        return new self(
            $name,
            sprintf('$this->%s()', new MethodName($reference))
        );
    }

    public function __toString(): string
    {
        return sprintf('->put(\'%s\', %s)', $this->name, $this->value);
    }
}
