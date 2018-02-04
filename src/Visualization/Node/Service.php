<?php
declare(strict_types = 1);

namespace Innmind\Compose\Visualization\Node;

use Innmind\Compose\Definition\{
    Name as ServiceName,
    Service\Constructor
};
use Innmind\Graphviz\{
    Node,
    Edge,
    Node\Name,
    Node\Shape
};
use Innmind\Url\UrlInterface;
use Innmind\Immutable\{
    MapInterface,
    SetInterface,
    Str
};

final class Service implements Node
{
    private $node;

    private function __construct(Node $node)
    {
        $this->node = $node;
    }

    public static function dependency(
        ServiceName $dependency,
        ServiceName $name,
        Constructor $constructor
    ): self {
        $constructor = Str::of((string) $constructor)->replace('\\', '\\\\');

        $node = Node\Node::named(
            (string) Str::of((string) $dependency)
                ->append('_'.$name)
                ->replace('.', '_')
        );
        $node->displayAs(
            (string) Str::of((string) $name)->append(
                ' ('.$constructor.')'
            )
        );

        return new self($node);
    }

    public function name(): Name
    {
        return $this->node->name();
    }

    /**
     * {@inheritdoc}
     */
    public function edges(): SetInterface
    {
        return $this->node->edges();
    }

    public function linkedTo(Node $node): Edge
    {
        return $this->node->linkedTo($node);
    }

    public function target(UrlInterface $url): Node
    {
        return $this->node->target($url);
    }
    public function displayAs(string $label): Node
    {
        return $this->node->displayAs($label);
    }

    public function shaped(Shape $shape): Node
    {
        return $this->node->shaped($shape);
    }

    public function hasAttributes(): bool
    {
        return $this->node->hasAttributes();
    }

    /**
     * {@inheritdoc}
     */
    public function attributes(): MapInterface
    {
        return $this->node->attributes();
    }
}
