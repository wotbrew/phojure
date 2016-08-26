<?php


namespace phojure;


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
    }

    function testBigMap()
    {
        $map = PersistentHashMap::create(
            [1, 2], 'foo',
            Coll::vector('foo', 1, 2), 'bar',
            'qux', [3, 4, 5, 6]
        );

        $this->assertEquals('foo', $map->valAt([1, 2]));
        $this->assertEquals('bar', $map->valAt(Coll::vector('foo', 1, 2)));

        $map2 = PersistentHashMap::create(
            $map, [1, 2, 3, 4]
        );

        $this->assertEquals([1, 2, 3, 4], $map2->valAt($map));

        Coll::seq($map);
    }
}