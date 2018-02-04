<?php
declare(strict_types = 1);

namespace Innmind\Compose\Visualization\Node;

use Innmind\Compose\Definition\{
    Name as ServiceName,
    Service,
    Service\Constructor
};
use Innmind\Graphviz\{
    Node,
    Edge,
    Node\Name,
    Node\Shape
};
use Innmind\Url\UrlInterface;
use Innmind\Colour\Colour;
use Innmind\Immutable\{
    MapInterface,
    SetInterface,
    Str
};

final class Element implements Node
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

        $node = self::buildNode($dependency->add($name));
        $node->displayAs(
            (string) Str::of((string) $name)->append(
                '\n('.$constructor.')'
            )
        );

        return new self($node);
    }

    public static function service(Service $service): self
    {
        $constructor = Str::of((string) $service->constructor())->replace('\\', '\\\\');

        $node = self::buildNode($service->name());
        $node->displayAs(
            (string) Str::of((string) $service->name())->append(
                '\n('.$constructor.')'
            )
        );

        if ($service->exposed()) {
            $node->shaped(
                Shape::house()->fillWithColor(Colour::fromString('#0f0'))
            );
        }

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

    public static function buildNode(ServiceName $name): Node
    {
        return Node\Node::named(
            (string) Str::of((string) $name)->replace('.', '_')
        );
    }
}
