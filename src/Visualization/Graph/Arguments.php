<?php
declare(strict_types = 1);

namespace Innmind\Compose\Visualization\Graph;

use Innmind\Compose\{
    Arguments as Args,
    Definition\Argument,
    Visualization\Node\Element
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
    MapInterface,
    Str
};

final class Arguments implements Graph
{
    private $graph;

    public function __construct(Args $arguments)
    {
        $this->graph = $arguments->all()->reduce(
            Graph\Graph::directed('arguments')
                ->fillWithColor(RGBA::fromString('#3399ff'))
                ->displayAs('Arguments'),
            static function(Graph $graph, Argument $argument): Graph {
                return $graph->add(Element::argument($argument));
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
