<?php


namespace phojure;


use Lavoiesl\PhpBenchmark\Benchmark;

class MapTest extends \PHPUnit_Framework_TestCase
{
    function testSmallMap()
    {
        $map = PersistentArrayMap::create(
           [1, 2], 'foo',
            Coll::vector('foo', 1, 2), 'bar',
            'qux', [3, 4, 5, 6]
        );

        $this->assertEquals('foo', $map->valAt([1, 2]));
        $this->assertEquals('bar', $map->valAt(Coll::vector('foo', 1, 2)));

        $map2 = PersistentArrayMap::create(
            $map, [1, 2, 3, 4]
        );

        $this->assertEquals([1, 2, 3, 4], $map2->valAt($map));

        $map3 = $map2->assoc('foo', 2);
        $this->assertEquals([1, 2, 3, 4], $map2->valAt($map));
        $this->assertEquals(2, $map3->valAt('foo'));

        $this->assertTrue(Val::eq($map, PersistentArrayMap::ofEntryTraversable($map)));
    }

    function testBigMap()
    {
        $map = PersistentHashMap::create(
            [1, 2], 'foo',
            Coll::vector('foo', 1, 2), 'bar',
            'qux', [3, 4, 5, 6]
        );

        $this->assertEquals('foo', Map::get($map, [1, 2]));
        $this->assertEquals('bar', Map::get($map, Coll::vector('foo', 1, 2)));

        $this->assertEquals('foo', $map([1, 2]), "Map is a function");
        $this->assertEquals('bar', $map(Coll::vector('foo', 1, 2)), "Map is a function");

        $map2 = PersistentHashMap::create(
            $map, [1, 2, 3, 4]
        );

        $this->assertEquals([1, 2, 3, 4], $map2->valAt($map));

        $map3 = $map2->assoc('foo', 2);
        $this->assertEquals([1, 2, 3, 4], $map2->valAt($map));
        $this->assertEquals(2, $map3->valAt('foo'));

        $this->assertTrue(Val::eq($map, PersistentHashMap::ofEntryTraversable($map)));

    }
}