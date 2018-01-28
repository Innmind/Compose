<?php
declare(strict_types = 1);

namespace Innmind\Compose;

use Innmind\Compose\{
    Definition\Name,
    Exception\ReferenceNotFound
};

final class Lazy
{
    private $name;
    private $definitions;

    public function __construct(
        Name $name,
        Definitions $definitions
    ) {
        if (!$definitions->has($name)) {
            throw new ReferenceNotFound((string) $name);
        }

        $this->name = $name;
        $this->definitions = $definitions;
    }

    public function load(): object
    {
        return $this->definitions->build($this->name);
    }
}
