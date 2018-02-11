<?php
declare(strict_types = 1);

namespace Innmind\Compose\Compilation;

use Innmind\Compose\{
    Definition\Argument as Definition,
    Exception\LogicException
};

final class Argument
{
    private $definition;

    public function __construct(Definition $argument)
    {
        $this->definition = $argument;
    }

    public function __toString(): string
    {
        $method = new MethodName($this->definition->name());
        $code = <<<PHP
    public function $method()
    {
        if (\$this->arguments->contains('{$this->definition->name()}')) {
            return \$this->arguments->get('{$this->definition->name()}');
        }

        {$this->buildDefault()}
        {$this->buildOptional()}
    }
PHP;

        return $code;
    }

    private function buildDefault(): string
    {
        if (!$this->definition->hasDefault()) {
            return '';
        }

        return sprintf(
            'return $this->%s();',
            new MethodName($this->definition->default())
        );
    }

    private function buildOptional(): string
    {
        if ($this->definition->hasDefault()) {
            return '';
        }

        if (!$this->definition->optional()) {
            return sprintf(
                'throw new %s(\'Missing argument "%s"\');',
                LogicException::class,
                $this->definition->name()
            );
        }

        return 'return null;';
    }
}
