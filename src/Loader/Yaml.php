<?php
declare(strict_types = 1);

namespace Innmind\Compose\Loader;

use Innmind\Compose\{
    Loader,
    Definitions,
    Arguments,
    Definition\Argument,
    Definition\Argument\Types,
    Definition\Name,
    Definition\Service,
    Definition\Service\Arguments as ServiceArguments,
    Definition\Service\Constructors,
    Exception\DomainException
};
use Innmind\Url\PathInterface;
use Innmind\Immutable\{
    Str,
    Stream,
    Map,
    Pair
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

    private $resolver;
    private $types;
    private $arguments;
    private $constructors;

    public function __construct(
        Types $types = null,
        ServiceArguments $arguments = null,
        Constructors $constructors = null
    ) {
        $this->resolver = new OptionsResolver;
        $this->resolver->setRequired(['expose', 'services']);
        $this->resolver->setDefined('arguments');
        $this->resolver->setAllowedTypes('arguments', 'array');
        $this->resolver->setAllowedTypes('expose', 'array');
        $this->resolver->setAllowedTypes('services', 'array');
        $this->resolver->setDefault('arguments', []);
        $this->types = $types ?? new Types;
        $this->arguments = $arguments ?? new ServiceArguments;
        $this->constructors = $constructors ?? new Constructors;
    }

    public function __invoke(PathInterface $definition): Definitions
    {
        $data = Lib::parseFile((string) $definition);
        $data = $this->resolver->resolve($data);

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

        $definitions = new Definitions(
            $arguments,
            ...$definitions->values()
        );

        return $exposed
            ->map(static function(string $as, string $name): Pair {
                return new Pair(
                    $as,
                    (string) Str::of($name)->substring(1) //remove the $ sign
                );
            })
            ->reduce(
                $definitions,
                static function(Definitions $definitions, string $as, string $name): Definitions {
                    return $definitions->expose(
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
            $key = Str::of($key);

            if (!is_array($value)) {
                throw new DomainException;
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
}
