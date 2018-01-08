<?php
declare(strict_types = 1);

namespace Fixture\Innmind\Compose;

final class ServiceFixture
{
    public $first;
    public $second;
    public $third;

    public function __construct(int $first, \stdClass $second, ...$third)
    {
        $this->first = $first;
        $this->second = $second;
        $this->third = $third;
    }
}
