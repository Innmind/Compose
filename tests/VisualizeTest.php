<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose;

use Innmind\Compose\{
    Visualize,
    Loader\Yaml
};
use Innmind\Url\Path;
use Innmind\Graphviz\{
    Graph,
    Layout\Dot
};
use PHPUnit\Framework\TestCase;

class VisualizeTest extends TestCase
{
    public function testInvokation()
    {
        $services = (new Yaml)(new Path('fixtures/container/full.yml'));

        $graph = (new Visualize)($services);

        $this->assertInstanceOf(Graph::class, $graph);

        $dot = (new Dot)($graph);
        $expected = <<<DOT
digraph G {
    subgraph cluster_dep {
        style="filled"
        fillcolor="#ffb600"
        label="dep"
    dep_fixture [label="fixture\\n(Fixture\\\\Innmind\\\\Compose\\\\ServiceFixture)"];
    dep_middle [label="middle\\n(Fixture\\\\Innmind\\\\Compose\\\\Stack\\\\Middle)"];
    }
    subgraph cluster_arguments {
        style="filled"
        fillcolor="#3399ff"
        label="Arguments"
    first [label="first\\n(int)"];
    second [label="second\\n(stdClass)"];
    third [label="third\\n(array)"];
    }
    inner_bar -> first;
    inner_bar -> second;
    inner_bar -> third;
    second -> inner_fallback_forBar [style="dotted", label="defaults to"];
    inner_baz -> first;
    inner_baz -> inner_fallback_forBar;
    fixtures -> inner_bar;
    fixtures -> inner_baz;
    fromDep -> second;
    fromDep -> dep_fixture;
    tunnel_37c76f70a65dbde92256c277c7c52469 -> stack_low;
    fixtureStack -> tunnel_37c76f70a65dbde92256c277c7c52469;
    inner_bar [shape="house", style="filled", fillcolor="#00ff00", label="inner.bar\\n(Fixture\\\\Innmind\\\\Compose\\\\ServiceFixture)"];
    inner_baz [shape="house", style="filled", fillcolor="#00ff00", label="inner.baz\\n(Fixture\\\\Innmind\\\\Compose\\\\ServiceFixture)"];
    inner_fallback_forBar [label="inner.fallback.forBar\\n(stdClass)"];
    ints [shape="house", style="filled", fillcolor="#00ff00", label="ints\\n(set<int>)"];
    strings [shape="house", style="filled", fillcolor="#00ff00", label="strings\\n(stream<string>)"];
    fixtures [shape="house", style="filled", fillcolor="#00ff00", label="fixtures\\n(map<string, Fixture\\\\Innmind\\\\Compose\\\\ServiceFixture>)"];
    stack_low [label="stack.low\\n(Fixture\\\\Innmind\\\\Compose\\\\Stack\\\\Low)"];
    fromDep [shape="house", style="filled", fillcolor="#00ff00", label="fromDep\\n(Fixture\\\\Innmind\\\\Compose\\\\ServiceFixture)"];
    tunnel_37c76f70a65dbde92256c277c7c52469 [label="tunnel_37c76f70a65dbde92256c277c7c52469\\n(Fixture\\\\Innmind\\\\Compose\\\\Stack\\\\Middle)"];
    fixtureStack [shape="house", style="filled", fillcolor="#00ff00", label="fixtureStack\\n(Fixture\\\\Innmind\\\\Compose\\\\Stack\\\\High)"];
}
DOT;

        $this->assertSame($expected, (string) $dot);
    }
}
