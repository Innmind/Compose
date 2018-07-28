<?php
declare(strict_types = 1);

namespace Innmind\Compose\Lazy;

use Innmind\Compose\Lazy;
use Innmind\Immutable\{
    StreamInterface,
    Stream as Base,
    MapInterface,
    Str,
    Type,
    Exception\LogicException
};

final class Stream implements StreamInterface
{
    use Type;

    private $type;
    private $spec;
    private $values;
    private $loaded;

    public function __construct(string $type, ...$values)
    {
        $this->type = Str::of($type);
        $this->spec = $this->getSpecificationFor($type);
        $this->values = Base::of('mixed', ...$values);
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
        if ($this->loaded) {
            return $this->loaded->toPrimitive();
        }

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
    public function offsetExists($offset): bool
    {
        return $this->values->offsetExists($offset);
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
        throw new LogicException('You can\'t modify a stream');
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        throw new LogicException('You can\'t modify a stream');
    }

    /**
     * {@inheritdoc}
     */
    public function get(int $index)
    {
        return $this->load($this->values->get($index));
    }

    /**
     * {@inheritdoc}
     */
    public function diff(StreamInterface $stream): StreamInterface
    {
        return $this->stream()->diff($stream);
    }

    /**
     * {@inheritdoc}
     */
    public function distinct(): StreamInterface
    {
        return $this->stream()->distinct();
    }

    /**
     * {@inheritdoc}
     */
    public function drop(int $size): StreamInterface
    {
        $self = clone $this;
        $self->values = $self->values->drop($size);

        return $self;
    }

    /**
     * {@inheritdoc}
     */
    public function dropEnd(int $size): StreamInterface
    {
        $self = clone $this;
        $self->values = $self->values->dropEnd($size);

        return $self;
    }

    /**
     * {@inheritdoc}
     */
    public function equals(StreamInterface $stream): bool
    {
        return $this->stream()->equals($stream);
    }

    /**
     * {@inheritdoc}
     */
    public function filter(callable $predicate): StreamInterface
    {
        return $this->stream()->filter($predicate);
    }

    /**
     * {@inheritdoc}
     */
    public function foreach(callable $function): StreamInterface
    {
        return $this->stream()->foreach($function);
    }

    /**
     * {@inheritdoc}
     */
    public function groupBy(callable $discriminator): MapInterface
    {
        return $this->stream()->groupBy($discriminator);
    }

    /**
     * {@inheritdoc}
     */
    public function first()
    {
        return $this->load($this->values->first());
    }

    /**
     * {@inheritdoc}
     */
    public function last()
    {
        return $this->load($this->values->last());
    }

    /**
     * {@inheritdoc}
     */
    public function contains($element): bool
    {
        return $this->stream()->contains($element);
    }

    /**
     * {@inheritdoc}
     */
    public function indexOf($element): int
    {
        return $this->stream()->indexOf($element);
    }

    /**
     * {@inheritdoc}
     */
    public function indices(): StreamInterface
    {
        return $this->values->indices();
    }

    /**
     * {@inheritdoc}
     */
    public function map(callable $function): StreamInterface
    {
        return $this->stream()->map($function);
    }

    /**
     * {@inheritdoc}
     */
    public function pad(int $size, $element): StreamInterface
    {
        $this->spec->validate($element);

        $self = clone $this;
        $self->values = $self->values->pad($size, $element);

        return $self;
    }

    /**
     * {@inheritdoc}
     */
    public function partition(callable $predicate): MapInterface
    {
        return $this->stream()->partition($predicate);
    }

    /**
     * {@inheritdoc}
     */
    public function slice(int $from, int $until): StreamInterface
    {
        $self = clone $this;
        $self->values = $self->values->slice($from, $until);

        return $self;
    }

    /**
     * {@inheritdoc}
     */
    public function splitAt(int $position): StreamInterface
    {
        $splitted = $this->values->splitAt($position);
        $first = clone $this;
        $last = clone $this;
        $first->values = $splitted->first();
        $last->values = $splitted->last();

        return Base::of(StreamInterface::class, $first, $last);
    }

    /**
     * {@inheritdoc}
     */
    public function take(int $size): StreamInterface
    {
        $self = clone $this;
        $self->values = $self->values->take($size);

        return $self;
    }

    /**
     * {@inheritdoc}
     */
    public function takeEnd(int $size): StreamInterface
    {
        $self = clone $this;
        $self->values = $self->values->takeEnd($size);

        return $self;
    }

    /**
     * {@inheritdoc}
     */
    public function append(StreamInterface $stream): StreamInterface
    {
        if ($stream instanceof self && !$this->loaded && !$stream->loaded) {
            return new self(
                (string) $this->type,
                ...$this->values,
                ...$stream->values
            );
        }

        return $this->stream()->append($stream);
    }

    /**
     * {@inheritdoc}
     */
    public function intersect(StreamInterface $stream): StreamInterface
    {
        return $this->stream()->intersect($stream);
    }

    /**
     * {@inheritdoc}
     */
    public function join(string $separator): Str
    {
        return $this->stream()->join($separator);
    }

    /**
     * {@inheritdoc}
     */
    public function add($element): StreamInterface
    {
        $this->spec->validate($element);

        $self = clone $this;
        $self->values = $self->values->add($element);

        return $self;
    }

    /**
     * {@inheritdoc}
     */
    public function sort(callable $function): StreamInterface
    {
        return $this->stream()->sort($function);
    }

    /**
     * {@inheritdoc}
     */
    public function reduce($carry, callable $reducer)
    {
        return $this->stream()->reduce($carry, $reducer);
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): StreamInterface
    {
        return new Base((string) $this->type);
    }

    /**
     * Return the same stream but in reverse order
     *
     * @return self<T>
     */
    public function reverse(): StreamInterface
    {
        $self = clone $this;
        $self->values = $self->values->reverse();

        return $self;
    }

    private function load($value)
    {
        if ($value instanceof Lazy) {
            return $value->load();
        }

        return $value;
    }

    private function stream(): Base
    {
        return $this->loaded ?? $this->loaded = Base::of(
            (string) $this->type,
            ...$this->toPrimitive()
        );
    }
}
