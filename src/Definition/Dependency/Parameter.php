<?php
declare(strict_types = 1);

namespace Innmind\Compose\Definition\Dependency;

use Innmind\Compose\{
    Definition\Name,
    Definition\Dependency,
    Services,
    Exception\ArgumentNotProvided,
    Exception\ArgumentNotDefined,
    Exception\NameNotNamespaced,
    Exception\ReferenceNotFound,
    Compilation\Dependency\Parameter as CompiledParameter
};
use Innmind\Immutable\Str;

final class Parameter
{
    private $name;
    private $value;
    private $reference;

    private function __construct(Name $name)
    {
        $this->name = $name;
    }

    public static function fromValue(Name $name, $value): self
    {
        if (!is_string($value)) {
            $self = new self($name);
            $self->value = $value;

            return $self;
        }

        $value = Str::of($value);

        if ((string) $value->substring(0, 1) !== '$') {
            $self = new self($name);
            $self->value = (string) $value;

            return $self;
        }

        $self = new self($name);
        $self->reference = new Name((string) $value->substring(1));

        return $self;
    }

    public function name(): Name
    {
        return $this->name;
    }

    public function resolve(Services $services)
    {
        if (!$this->reference instanceof Name) {
            return $this->value;
        }

        try {
            return $services->arguments()->get($this->reference);
        } catch (ArgumentNotProvided $e) {
            if ($e->argument()->hasDefault()) {
                return $services->build($e->argument()->default());
            }

            if ($e->argument()->optional()) {
                return null;
            }

            throw $e;
        } catch (ArgumentNotDefined $e) {
            //pass
        }

        try {
            return $services->dependencies()->build($this->reference);
        } catch (ReferenceNotFound $e) {
            //pass
        }

        return $services->build($this->reference);
    }

    public function refersTo(Dependency $dependeny): bool
    {
        if (!$this->reference instanceof Name) {
            return false;
        }

        try {
            $root = $this->reference->root();
        } catch (NameNotNamespaced $e) {
            return false;
        }

        if (!$root->equals($dependeny->name())) {
            return false;
        }

        return $dependeny->has($this->reference->withoutRoot());
    }

    public function compile(): CompiledParameter
    {
        if (!$this->reference instanceof Name) {
            return CompiledParameter::raw($this->name, $this->value);
        }

        return CompiledParameter::reference($this->name, $this->reference);
    }
}
