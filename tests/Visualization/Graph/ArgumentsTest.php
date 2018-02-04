<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose\Visualization\Graph;

use Innmind\Compose\{
    Visualization\Graph\Arguments,
    Arguments as Args,
    Definition\Argument,
    Definition\Argument\Type\Instance,
    Definition\Name
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

class ArgumentsTest extends TestCase
{
    private $graph;

    public function setUp()
    {
        $this->graph = new Arguments(
            new Args(
                new Argument(
                    new Name('foo'),
                    new Instance('stdClass')
                )
            )
        );
    }

    public function testInterface()
    {
        $this->assertInstanceOf(Graph::class, $this->graph);
        $this->assertCount(1, $this->graph->attributes());
        $this->assertSame('Arguments', $this->graph->attributes()->get('label'));
        $this->assertCount(1, $this->graph->roots());
        $this->assertSame('foo', (string) $this->graph->roots()->current()->name());
        $this->assertCount(1, $this->graph->roots()->current()->attributes());
        $this->assertSame(
            'foo\n(stdClass)',
            $this->graph->roots()->current()->attributes()->get('label')
        );
    }

    public function testIsDirected()
    {
        $this->assertTrue($this->graph->isDirected());
    }

    public function testName()
    {
        $this->assertSame('arguments', (string) $this->graph->name());
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
        $this->assertCount(2, $this->graph->attributes());
    }
}
