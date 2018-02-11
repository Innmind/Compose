<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose\Visualization\Graph;

use Innmind\Compose\{
    Visualization\Graph\Dependency,
    Definition\Name as ServiceName,
    Definition\Service\Constructor
};
use Innmind\Graphviz\{
    Graph,
    Node\Node
};
use Innmind\Colour\Colour;
use Innmind\Url\UrlInterface;
use Innmind\Immutable\{
    Map,
    Str
};
use PHPUnit\Framework\TestCase;

class DependencyTest extends TestCase
{
    private $graph;

    public function setUp()
    {
        $this->graph = new Dependency(
            new ServiceName('dep'),
            (new Map(ServiceName::class, Constructor::class))->put(
                new ServiceName('service'),
                Constructor\Construct::fromString(Str::of('stdClass'))
            )
        );
    }

    public function testThrowWhenInvalidServicesMap()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage(sprintf(
            'Argument 2 must be of type MapInterface<%s, %s>',
            ServiceName::class,
            Constructor::class
        ));

        new Dependency(
            new ServiceName('dep'),
            new Map('int', 'int')
        );
    }

    public function testInterface()
    {
        $this->assertInstanceOf(Graph::class, $this->graph);
        $this->assertCount(3, $this->graph->attributes());
        $this->assertSame('dep', $this->graph->attributes()->get('label'));
        $this->assertSame('filled', $this->graph->attributes()->get('style'));
        $this->assertSame('#ffb600', $this->graph->attributes()->get('fillcolor'));
    }

    public function testIsDirected()
    {
        $this->assertTrue($this->graph->isDirected());
    }

    public function testName()
    {
        $this->assertSame('dep', (string) $this->graph->name());
    }

    public function testCluster()
    {
        $this->assertInstanceOf(
            Graph::class,
            $this->graph->cluster(Graph\Graph::directed())
        );
        $this->assertCount(1, $this->graph->clusters());
    }

    public function testClusters()
    {
        $this->assertCount(0, $this->graph->clusters());
    }

    public function testAdd()
    {
        $this->assertInstanceOf(
            Graph::class,
            $this->graph->add(Node::named('foo'))
        );
        $this->assertCount(2, $this->graph->roots());
    }

    public function testRoots()
    {
        $this->assertCount(1, $this->graph->roots());
    }

    public function testNodes()
    {
        $this->assertCount(1, $this->graph->nodes());
    }

    public function testDisplayAs()
    {
        $this->assertInstanceOf(
            Graph::class,
            $this->graph->displayAs('watev')
        );
        $this->assertSame('watev', $this->graph->attributes()->get('label'));
    }

    public function testFillWithColor()
    {
        $this->assertInstanceOf(
            Graph::class,
            $this->graph->fillWithColor(Colour::fromString('red'))
        );
        $this->assertSame('#ff0000', $this->graph->attributes()->get('fillcolor'));
        $this->assertSame('filled', $this->graph->attributes()->get('style'));
    }

    public function testColorizeBorderWith()
    {
        $this->assertInstanceOf(
            Graph::class,
            $this->graph->colorizeBorderWith(Colour::fromString('red'))
        );
        $this->assertSame('#ff0000', $this->graph->attributes()->get('color'));
    }

    public function testTarget()
    {
        $this->assertInstanceOf(
            Graph::class,
            $this->graph->target($this->createMock(UrlInterface::class))
        );
        $this->assertCount(4, $this->graph->attributes());
    }
}
