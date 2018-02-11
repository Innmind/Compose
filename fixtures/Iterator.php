<?php
declare(strict_types = 1);

namespace Fixture\Innmind\Compose;

final class Iterator implements \IteratorAggregate
{
    private $values;

    public function __construct(...$values)
    {
        $this->values = $values;
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->values);
    }
}
