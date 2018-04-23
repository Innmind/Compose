<?php
declare(strict_types = 1);

namespace Innmind\Compose;

use Innmind\Compose\{
    Arguments as Args,
    Definition\Argument as Arg,
    Definition\Name,
    Definition\Service,
    Definition\Service\Constructor,
    Definition\Service\Argument,
    Definition\Service\Argument\HoldReference,
    Definition\Service\Argument\HoldReferences,
    Visualization\Graph\Dependency,
    Visualization\Graph\Arguments,
    Visualization\Node\Element
};
use Innmind\Graphviz\{
    Graph,
    Node\Node
};
use Innmind\Immutable\{
    MapInterface,
    Set,
    Str,
    Map
};

final class Visualize
{
    public function __invoke(Services $services): Graph
    {
        $graph = $this
            ->loadClusters($services->dependencies())
            ->reduce(
                Graph\Graph::directed('G', Graph\Rankdir::leftToRight()),
                static function(Graph $graph, Graph $cluster): Graph {
                    return $graph->cluster($cluster);
                }
            )
            ->cluster(new Arguments($services->arguments()));

        $nodes = $this->buildNodes($services);
        $this->linkNodes($nodes);

        $graph = $nodes->reduce(
            $graph,
            static function(Graph $graph, string $name, array $pair): Graph {
                return $graph->add($pair[0]);
            }
        );

        return $this->linkDefaults($graph, $services->arguments(), $nodes);
    }

    private function loadClusters(Dependencies $dependencies): Set
    {
        return $dependencies->exposed()->reduce(
            Set::of(Graph::class),
            static function(Set $clusters, Name $dependency, MapInterface $services): Set {
                return $clusters->add(new Dependency($dependency, $services));
            }
        );
    }

    private function buildNodes(Services $services): Map
    {
        return $services
            ->all()
            ->filter(static function(Service $service): bool {
                return !$service->decorates();
            })
            ->reduce(
                new Map('string', 'array'),
                static function(Map $nodes, Service $service): Map {
                    $node = Element::service($service);

                    return $nodes->put(
                        (string) $node->name(),
                        [$node, $service]
                    );
                }
            );
    }

    private function linkNodes(Map $nodes): void
    {
        $nodes
            ->values()
            ->filter(static function(array $pair): bool {
                //do not try to link nodes for services that do not depend on
                //other services
                return $pair[1]
                    ->arguments()
                    ->filter(static function(Argument $argument): bool {
                        return $argument instanceof HoldReference;
                    })
                    ->size() > 0;
            })
            ->foreach(static function(array $pair) use ($nodes): void {
                $pair[1]
                    ->arguments()
                    ->filter(static function(Argument $argument): bool {
                        return $argument instanceof HoldReference;
                    })
                    ->foreach(static function(HoldReference $argument) use ($pair, $nodes): void {
                        $name = (string) Element::build($argument->reference())->name();

                        //an argument or a dependency
                        if (!$nodes->contains($name)) {
                            $pair[0]->linkedTo(Node::named($name));

                            return;
                        }

                        $pair[0]->linkedTo(
                            $nodes->get($name)[0]
                        );
                    });
            });
        $nodes
            ->values()
            ->filter(static function(array $pair): bool {
                //do not try to link nodes for services that do not depend on
                //other services
                return $pair[1]
                    ->arguments()
                    ->filter(static function(Argument $argument): bool {
                        return $argument instanceof HoldReferences;
                    })
                    ->size() > 0;
            })
            ->foreach(static function(array $pair) use ($nodes): void {
                $pair[1]
                    ->arguments()
                    ->filter(static function(Argument $argument): bool {
                        return $argument instanceof HoldReferences;
                    })
                    ->reduce(
                        Set::of(Name::class),
                        static function(Set $names, HoldReferences $argument): Set {
                            return $names->merge($argument->references());
                        }
                    )
                    ->foreach(static function(Name $name) use ($pair, $nodes): void {
                        $name = (string) Element::build($name)->name();

                        //an argument or a dependency
                        if (!$nodes->contains($name)) {
                            $pair[0]->linkedTo(Node::named($name));

                            return;
                        }

                        $pair[0]->linkedTo(
                            $nodes->get($name)[0]
                        );
                    });
            });
    }

    private function linkDefaults(Graph $graph, Args $arguments, Map $nodes): Graph
    {
        return $arguments
            ->all()
            ->filter(static function(Arg $argument): bool {
                return $argument->hasDefault();
            })
            ->reduce(
                $graph,
                static function(Graph $graph, Arg $argument) use ($nodes): Graph {
                    $node = Element::build($argument->name());
                    $default = Element::build($argument->default());
                    $default = $nodes->get((string) $default->name())[0];
                    $edge = $node->linkedTo($default);
                    $edge
                        ->dotted()
                        ->displayAs('defaults to');

                    return $graph->add($node);
                }
            );
    }
}
