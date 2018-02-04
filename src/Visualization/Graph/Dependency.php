<?php
declare(strict_types = 1);

namespace Innmind\Compose\Visualization\Graph;

use Innmind\Compose\{
    Definition\Name as ServiceName,
    Definition\Service\Constructor,
    Visualization\Node\Service
};
use Innmind\Graphviz\{
    Graph,
    Node,
    Graph\Name
};
use Innmind\Colour\RGBA;
use Innmind\Url\UrlInterface;
use Innmind\Immutable\{
    SetInterface,
    MapInterface
};

final class Dependency implements Graph
{
    private $graph;

    public function __construct(ServiceName $dependency, MapInterface $services)
    {
        if (
            (string) $services->keyType() !== ServiceName::class ||
            (string) $services->valueType() !== Constructor::class
        ) {
            throw new \TypeError(sprintf(
                'Argument 2 must be of type MapInterface<%s, %s>',
                ServiceName::class,
                Constructor::class
            ));
        }

        $this->graph = $services->reduce(
            Graph\Graph::directed((string) $dependency)->displayAs((string) $dependency),
            static function(Graph $graph, ServiceName $service, Constructor $constructor) use ($dependency): Graph {
                return $graph->add(Service::dependency(
                    $dependency,
                    $service,
                    $constructor
                ));
            }
        );
    }

    public function isDirected(): bool
    {
        return $this->graph->isDirected();
    }

    public function name(): Name
    {
        return $this->graph->name();
    }

    public function cluster(Graph $cluster): Graph
    {
        return $this->graph->cluster($cluster);
    }

    /**
     * {@inheritdoc}
     */
    public function clusters(): SetInterface
    {
        return $this->graph->clusters();
    }

    public function add(Node $node): Graph
    {
        return $this->graph->add($node);
    }

    /**
     * {@inheritdoc}
     */
    public function roots(): SetInterface
    {
        return $this->graph->roots();
    }

    /**
     * {@inheritdoc}
     */
    public function nodes(): SetInterface
    {
        return $this->graph->nodes();
    }

    public function displayAs(string $label): Graph
    {
        return $this->graph->displayAs($label);
    }

    public function fillWithColor(RGBA $color): Graph
    {
        return $this->graph->fillWithColor($color);
    }

    public function colorizeBorderWith(RGBA $color): Graph
    {
        return $this->graph->colorizeBorderWith($color);
    }

    public function target(UrlInterface $url): Graph
    {
        return $this->graph->target($url);
    }

    /**
     * {@inheritdoc}
     */
    public function attributes(): MapInterface
    {
        return $this->graph->attributes();
    }
}
