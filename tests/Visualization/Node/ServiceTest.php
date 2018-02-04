<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose\Visualization\Node;

use Innmind\Compose\{
    Visualization\Node\Service,
    Definition\Name as ServiceName,
    Definition\Service\Constructor\Construct
};
use Innmind\Graphviz\{
    Node,
    Node\Shape,
    Edge
};
use Innmind\Url\UrlInterface;
use Innmind\Immutable\Str;
use PHPUnit\Framework\TestCase;
use Fixture\Innmind\Compose\ServiceFixture;

class ServiceTest extends TestCase
{
    private $node;

    public function setUp()
    {
        $this->node = Service::dependency(
            new ServiceName('dep'),
            new ServiceName('foo'),
            Construct::fromString(Str::of(ServiceFixture::class))
        );
    }

    public function testInterface()
    {
        $this->assertInstanceOf(Node::class, $this->node);
        $this->assertTrue($this->node->hasAttributes());
        $this->assertCount(1, $this->node->attributes());
        $this->assertTrue($this->node->attributes()->contains('label'));
        $this->assertSame(
            'foo (Fixture\\\\Innmind\\\\Compose\\\\ServiceFixture)',
            $this->node->attributes()->get('label')
        );
    }

    public function testName()
    {
        $this->assertSame(
            'dep_foo',
            (string) $this->node->name()
        );
    }

    public function testEdges()
    {
        $this->assertCount(0, $this->node->edges());
    }

    public function testLinkedTo()
    {
        $this->assertInstanceOf(
            Edge::class,
            $this->node->linkedTo($this->createMock(Node::class))
        );
        $this->assertCount(1, $this->node->edges());
    }

    public function testTarget()
    {
        $this->assertInstanceOf(
            Node::class,
            $this->node->target($this->createMock(UrlInterface::class))
        );
        $this->assertCount(2, $this->node->attributes());
    }

    public function testDisplayAs()
    {
        $this->assertInstanceOf(
            Node::class,
            $this->node->displayAs('watev')
        );
        $this->assertSame('watev', (string) $this->node->attributes()->get('label'));
    }

    public function testShaped()
    {
        $this->assertInstanceOf(
            Node::class,
            $this->node->shaped(Shape::egg())
        );
        $this->assertCount(2, $this->node->attributes());
    }
}
