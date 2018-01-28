<?php
declare(strict_types = 1);

namespace Innmind\Compose\Lazy;

use Innmind\Compose\Lazy;
use Innmind\Immutable\{
    MapInterface,
    Map as Base,
    Str,
    Sequence,
    Pair,
    SetInterface,
    StreamInterface,
    Exception\LogicException
};

final class Map implements MapInterface
{
    private $keyType;
    private $valueType;
    private $values;
    private $loaded;

    public function __construct(string $keyType, string $valueType)
    {
        $this->keyType = new Str($keyType);
        $this->valueType = new Str($valueType);
        $this->values = new Base('mixed', 'mixed');
    }

    public static function of(string $keyType, string $valueType, Pair ...$values): self
    {
        $self = new self($keyType, $valueType);
        $self->values = Sequence::of(...$values)->reduce(
            $self->values,
            static function(Base $map, Pair $pair): Base {
                return $map->put(
                    $pair->key(),
                    $pair->value()
                );
            }
        );

        return $self;
    }

    /**
     * {@inheritdoc}
     */
    public function keyType(): Str
    {
        return $this->keyType;
    }

    /**
     * {@inheritdoc}
     */
    public function valueType(): Str
    {
        return $this->valueType;
    }

    /**
     * {@inheritdoc}
     */
    public function size(): int
    {
        return $this->values->size();
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return $this->values->count();
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        return $this->load($this->values->current());
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return $this->load($this->values->key());
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        $this->values->next();
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        $this->values->rewind();
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        return $this->values->valid();
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset): bool
    {
        return $this->contains($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        throw new LogicException('You can\'t modify a map');
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        throw new LogicException('You can\'t modify a map');
    }

    /**
     * {@inheritdoc}
     */
    public function put($key, $value): MapInterface
    {
        return $this->loadedMap()->put($key, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function get($key)
    {
        return $this->loadedMap()->get($key);
    }

    /**
     * {@inheritdoc}
     */
    public function contains($key): bool
    {
        return $this->loadedMap()->contains($key);
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): MapInterface
    {
        return new Base((string) $this->keyType, (string) $this->valueType);
    }

    /**
     * {@inheritdoc}
     */
    public function equals(MapInterface $map): bool
    {
        return $this->loadedMap()->equals($map);
    }

    /**
     * {@inheritdoc}
     */
    public function filter(callable $predicate): MapInterface
    {
        return $this->loadedMap()->filter($predicate);
    }

    /**
     * {@inheritdoc}
     */
    public function foreach(callable $function): MapInterface
    {
        return $this->loadedMap()->foreach($function);
    }

    /**
     * {@inheritdoc}
     */
    public function groupBy(callable $discriminator): MapInterface
    {
        return $this->loadedMap()->groupBy($discriminator);
    }

    /**
     * {@inheritdoc}
     */
    public function keys(): SetInterface
    {
        return $this->loadedMap()->keys();
    }

    /**
     * {@inheritdoc}
     */
    public function values(): StreamInterface
    {
        return $this->loadedMap()->values();
    }

    /**
     * {@inheritdoc}
     */
    public function map(callable $function): MapInterface
    {
        return $this->loadedMap()->map($function);
    }

    /**
     * {@inheritdoc}
     */
    public function join(string $separator): Str
    {
        return $this->loadedMap()->join($separator);
    }

    /**
     * {@inheritdoc}
     */
    public function remove($key): MapInterface
    {
        return $this->loadedMap()->remove($key);
    }

    /**
     * {@inheritdoc}
     */
    public function merge(MapInterface $map): MapInterface
    {
        return $this->loadedMap()->merge($map);
    }

    /**
     * {@inheritdoc}
     */
    public function partition(callable $predicate): MapInterface
    {
        return $this->loadedMap()->partition($predicate);
    }

    /**
     * {@inheritdoc}
     */
    public function reduce($carry, callable $reducer)
    {
        return $this->loadedMap()->reduce($carry, $reducer);
    }

    private function load($value)
    {
        if ($value instanceof Lazy) {
            return $value->load();
        }

        return $value;
    }

    private function loadedMap(): Base
    {
        return $this->loaded ?? $this->loaded = $this->values->reduce(
            $this->clear(),
            function(Base $map, $key, $value): Base {
                return $map->put(
                    $this->load($key),
                    $this->load($value)
                );
            }
        );
    }
}
