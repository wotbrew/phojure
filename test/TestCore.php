<?php

namespace phojure;

class TestCore extends \PHPUnit_Framework_TestCase
{
    function testSeq()
    {
        $this->assertEquals(1, Coll::first([1, 2, 3]));
    }

    function testLazySeq()
    {
        $this->assertNotNull(Coll::repeat("hello"));
        $this->assertNotNull(Coll::drop(1000000000000, Coll::repeat("hello")));

        $this->assertEquals("hello", Coll::first(Coll::drop(100000, Coll::repeat("hello"))));
    }

    function testLast()
    {
       $this->assertEquals("foo", Coll::last(Coll::take(100000, Coll::repeat("foo"))));
    }
    
    function testFirst()
    {
        $this->assertEquals(2,
            Core::threadl([1, 2, 3])
                ->pipe(Coll::$map, function($x){return $x + 1;})
                ->pipe(Coll::$first)
                ->val());
    }
}