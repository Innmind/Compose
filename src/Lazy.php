<?php
declare(strict_types = 1);

namespace Innmind\Compose;

use Innmind\Compose\{
    Definition\Name,
    Exception\ReferenceNotFound
};

final class Lazy
{
    private $load;

    public function __construct(callable $load)
    {
        $this->load = $load;
    }

    public static function service(
        Name $name,
        Services $services
    ): self {
        if (!$services->has($name)) {
            throw new ReferenceNotFound((string) $name);
        }

        return new self(static function() use ($services, $name): object {
            return $services->build($name);
        });
    }

    public function load(): object
    {
        return ($this->load)();
    }
}
