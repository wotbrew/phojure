<?php

/**
 * Created by PhpStorm.
 * User: danielstone
 * Date: 22/06/2016
 * Time: 14:16
 */
namespace phojure;

class TestCore extends \PHPUnit_Framework_TestCase
{
    function testSeq(){
        $this->assertEquals(1, Core::first([1, 2, 3]));
    }

    function testLazySeq(){
        $this->assertNotNull(Core::repeat("hello"));
        $this->assertNotNull(Core::drop(1000000000000, Core::repeat("hello")));

        $this->assertEquals("hello", Core::first(Core::drop(100000, Core::repeat("hello"))));
    }

    function testLast(){
       $this->assertEquals("foo", Core::last(Core::take(100000, Core::repeat("foo"))));
    }
}