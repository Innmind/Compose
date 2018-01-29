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
    private $services;

    public function __construct(
        Name $name,
        Services $services
    ) {
        if (!$services->has($name)) {
            throw new ReferenceNotFound((string) $name);
        }

        $this->name = $name;
        $this->services = $services;
    }

    public function load(): object
    {
        return $this->services->build($this->name);
    }
}
