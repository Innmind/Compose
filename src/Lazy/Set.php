<?php
declare(strict_types = 1);

namespace Innmind\Compose\Lazy;

use Innmind\Compose\Lazy;
use Innmind\Immutable\{
    SetInterface,
    Set as Base,
    Str,
    MapInterface,
    StreamInterface
};

final class Set implements SetInterface
{
    private $type;
    private $values;

    public function __construct(string $type)
    {
        $this->type = Str::of($type);
        $this->values = Base::of('mixed');
    }

    public static function of(string $type, ...$values): self
    {
        $self = new self($type);
        $self->values = Base::of('mixed', ...$values);

        return $self;
    }

    /**
     * {@inheritdoc}
     */
    public function type(): Str
    {
        return $this->type;
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
        return $this->values->size();
    }

    /**
     * {@inheritdoc}
     */
    public function toPrimitive()
    {
        return $this->values->reduce(
            [],
            function(array $values, $value): array {
                $values[] = $this->load($value);

                return $values;
            }
        );
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
        return $this->values->key();
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
    public function intersect(SetInterface $set): SetInterface
    {
        return $this->set()->intersect($set);
    }

    /**
     * {@inheritdoc}
     */
    public function add($element): SetInterface
    {
        return $this->set()->add($element);
    }

    /**
     * {@inheritdoc}
     */
    public function contains($element): bool
    {
        return $this->set()->contains($element);
    }

    /**
     * {@inheritdoc}
     */
    public function remove($element): SetInterface
    {
        return $this->set()->remove($element);
    }

    /**
     * {@inheritdoc}
     */
    public function diff(SetInterface $set): SetInterface
    {
        return $this->set()->diff($set);
    }

    /**
     * {@inheritdoc}
     */
    public function equals(SetInterface $set): bool
    {
        return $this->set()->equals($set);
    }

    /**
     * {@inheritdoc}
     */
    public function filter(callable $predicate): SetInterface
    {
        return $this->set()->filter($predicate);
    }

    /**
     * {@inheritdoc}
     */
    public function foreach(callable $function): SetInterface
    {
        $this->values->foreach(function($value) use ($function): void {
            $function($this->load($value));
        });

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function groupBy(callable $discriminator): MapInterface
    {
        return $this->set()->groupBy($discriminator);
    }

    /**
     * {@inheritdoc}
     */
    public function map(callable $function): SetInterface
    {
        return $this->set()->map($function);
    }

    /**
     * {@inheritdoc}
     */
    public function partition(callable $predicate): MapInterface
    {
        return $this->set()->partition($predicate);
    }

    /**
     * {@inheritdoc}
     */
    public function join(string $separator): Str
    {
        return $this->set()->join($separator);
    }

    /**
     * {@inheritdoc}
     */
    public function sort(callable $function): StreamInterface
    {
        return $this->set()->sort($function);
    }

    /**
     * {@inheritdoc}
     */
    public function merge(SetInterface $set): SetInterface
    {
        return $this->set()->merge($set);
    }

    /**
     * {@inheritdoc}
     */
    public function reduce($carry, callable $reducer)
    {
        return $this->set()->reduce($carry, $reducer);
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): SetInterface
    {
        return Base::of((string) $this->type);
    }

    private function load($value)
    {
        if ($value instanceof Lazy) {
            return $value->load();
        }

        return $value;
    }

    private function set(): Base
    {
        return Base::of((string) $this->type, ...$this->toPrimitive());
    }
}
