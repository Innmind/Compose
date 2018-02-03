<?php
declare(strict_types = 1);

namespace Innmind\Compose\Loader;

use Innmind\Compose\{
    Loader,
    Loader\PathResolver\Relative,
    Services,
    Arguments,
    Dependencies,
    Definition\Argument,
    Definition\Argument\Types,
    Definition\Name,
    Definition\Service,
    Definition\Service\Arguments as ServiceArguments,
    Definition\Service\Constructors,
    Definition\Dependency,
    Definition\Dependency\Parameter,
    Exception\DomainException
};
use Innmind\Url\{
    PathInterface,
    Path
};
use Innmind\Immutable\{
    Str,
    Stream,
    Map,
    Pair,
    Sequence
};
use Symfony\Component\{
    Yaml\Yaml as Lib,
    OptionsResolver\OptionsResolver
};

final class Yaml implements Loader
{
    private const ARGUMENT_PATTERN = '~^(?<optional>\?)?(?<type>.+)( \?\? \$.+)?$~';
    private const ARGUMENT_DEFAULT_PATTERN = '~( \?\? \$(?<default>.+))$~';
    private const SERVICE_NAME = "~^(?<name>[a-zA-Z0-9]+)[\s ](?<constructor>.+)$~"; //split on space or non breaking space
    private const STACK_NAME = "~^(?<name>[a-zA-Z0-9]+)[\s ]stack$~"; //split on space or non breaking space
    private const DEPENDENCY_NAME = "~^(?<name>[a-zA-Z0-9]+)[\s ](?<path>.+)$~"; //split on space or non breaking space

    private $resolver;
    private $types;
    private $arguments;
    private $constructors;
    private $stacks;

    public function __construct(
        Types $types = null,
        ServiceArguments $arguments = null,
        Constructors $constructors = null,
        PathResolver $pathResolver = null
    ) {
        $this->resolver = new OptionsResolver;
        $this->resolver->setRequired(['expose', 'services']);
        $this->resolver->setDefined(['arguments', 'dependencies']);
        $this->resolver->setAllowedTypes('arguments', 'array');
        $this->resolver->setAllowedTypes('dependencies', 'array');
        $this->resolver->setAllowedTypes('expose', 'array');
        $this->resolver->setAllowedTypes('services', 'array');
        $this->resolver->setDefault('arguments', []);
        $this->resolver->setDefault('dependencies', []);
        $this->types = $types ?? new Types;
        $this->arguments = $arguments ?? new ServiceArguments;
        $this->constructors = $constructors ?? new Constructors;
        $this->resolvePath = $pathResolver ?? new Relative;
        $this->stacks = new Map(Name::class, Sequence::class);
    }

    public function __invoke(PathInterface $definition): Services
    {
        $data = Lib::parseFile((string) $definition);
        $data = $this->resolver->resolve($data);

        $dependencies = $this->buildDependencies(
            $definition,
            $data['dependencies']
        );

        $this->stacks = $this->stacks->clear();

        $arguments = $this->buildArguments($data['arguments']);
        $definitions = $this->buildDefinitions(
            Stream::of('string'),
            $data['services']
        );

        $exposed = Map::of(
            'string',
            'string',
            array_keys($data['expose']),
            array_values($data['expose'])
        );

        $services = new Services(
            $arguments,
            $dependencies,
            ...$definitions->values()
        );
        $services = $this->buildStacks($services);

        return $exposed
            ->map(static function(string $as, string $name): Pair {
                return new Pair(
                    $as,
                    (string) Str::of($name)->substring(1) //remove the $ sign
                );
            })
            ->reduce(
                $services,
                static function(Services $services, string $as, string $name): Services {
                    return $services->expose(
                        new Name($name),
                        new Name($as)
                    );
                }
            );
    }

    private function buildArguments(array $definitions): Arguments
    {
        $arguments = [];

        foreach ($definitions as $name => $type) {
            $arguments[] = $this->buildArgument($name, Str::of($type)->trim());
        }

        return new Arguments(...$arguments);
    }

    private function buildArgument(string $name, Str $type): Argument
    {
        if (!$type->matches(self::ARGUMENT_PATTERN)) {
            throw new DomainException;
        }

        $components = $type->capture(self::ARGUMENT_PATTERN);

        $argument = new Argument(
            new Name($name),
            $this->types->load(
                $components
                    ->get('type')
                    ->pregReplace(
                        self::ARGUMENT_DEFAULT_PATTERN,
                        ''
                    )
            )
        );

        if (
            $components->contains('optional') &&
            !$components->get('optional')->empty()
        ) {
            $argument = $argument->makeOptional();
        }

        if ($type->matches(self::ARGUMENT_DEFAULT_PATTERN)) {
            $argument = $argument->defaultsTo(new Name(
                (string) $type
                    ->capture(self::ARGUMENT_DEFAULT_PATTERN)
                    ->get('default')
            ));
        }

        return $argument;
    }

    private function buildDefinitions(Stream $namespace, array $definitions): Map
    {
        $services = new Map('string', Service::class);

        foreach ($definitions as $key => $value) {
            $key = Str::of((string) $key);

            if (!is_array($value)) {
                throw new DomainException;
            }

            if ($key->matches(self::STACK_NAME)) {
                $this->registerStack($namespace, $key, $value);

                continue;
            }

            if (!$key->matches(self::SERVICE_NAME)) {
                $services = $services->merge(
                    $this->buildDefinitions(
                        $namespace->add((string) $key),
                        $value
                    )
                );

                continue;
            }

            $service = $this->buildService($namespace, $key, $value);
            $services = $services->put(
                (string) $service->name(),
                $service
            );
        }

        return $services;
    }

    private function buildService(
        Stream $namespace,
        Str $name,
        array $arguments
    ): Service {
        $components = $name->capture(self::SERVICE_NAME);

        foreach ($arguments as &$argument) {
            $argument = $this->arguments->load($argument);
        }

        return new Service(
            new Name(
                (string) $namespace
                    ->add((string) $components->get('name'))
                    ->join('.')
            ),
            $this->constructors->load($components->get('constructor')->trim('  ')), //space and non breaking space
            ...$arguments
        );
    }

    private function registerStack(Stream $namespace, Str $key, array $stack): void
    {
        $name = (string) $namespace
            ->add((string) $key->capture(self::STACK_NAME)->get('name'))
            ->join('.');

        $this->stacks = $this->stacks->put(
            new Name($name),
            Sequence::of(...$stack)->map(static function(string $name): Name {
                return new Name(
                    (string) Str::of($name)->substring(1) //remove the $ sign
                );
            })
        );
    }

    private function buildStacks(Services $services): Services
    {
        return $this->stacks->reduce(
            $services,
            function(Services $services, Name $name, Sequence $stack): Services {
                return $services->stack($name, ...$stack);
            }
        );
    }

    private function buildDependencies(
        PathInterface $origin,
        array $dependencies
    ): Dependencies {
        $deps = [];

        foreach ($dependencies as $name => $parameters) {
            $deps[] = $this->buildDependency($origin, $name, $parameters);
        }

        return new Dependencies(...$deps);
    }

    private function buildDependency(
        PathInterface $origin,
        string $name,
        array $parameters
    ): Dependency {
        $name = Str::of($name);

        if (!$name->matches(self::DEPENDENCY_NAME)) {
            throw new DomainException;
        }

        $components = $name->capture(self::DEPENDENCY_NAME);
        $services = $this(($this->resolvePath)(
            $origin,
            new Path((string) $components->get('path'))
        ));
        $params = [];

        foreach ($parameters as $param => $value) {
            $params[] = Parameter::fromValue(new Name($param), $value);
        }

        return new Dependency(
            new Name((string) $components->get('name')),
            $services,
            ...$params
        );
    }
}
