<?php

namespace phojure;

class TestCore extends \PHPUnit_Framework_TestCase
{
    function testSeq()
    {
        $this->assertEquals(1, Core::first([1, 2, 3]));
    }

    function testLazySeq()
    {
        $this->assertNotNull(Core::repeat("hello"));
        $this->assertNotNull(Core::drop(1000000000000, Core::repeat("hello")));

        $this->assertEquals("hello", Core::first(Core::drop(100000, Core::repeat("hello"))));
    }

    function testLast()
    {
       $this->assertEquals("foo", Core::last(Core::take(100000, Core::repeat("foo"))));
    }
    
    function testFirst()
    {
        $this->assertEquals(2,
            Core::threadl([1, 2, 3])
                ->pipe(Core::$map, [function($x){return $x + 1;}])
                ->pipe(Core::$first, [])
                ->val());
    }
}