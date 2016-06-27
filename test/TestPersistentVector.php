<?php


namespace phojure;


class TestPersistentVector extends \PHPUnit_Framework_TestCase
{
    function testFoo()
    {
        $this->assertEquals(1, 1);
    }

    function testAdd()
    {
        $vec = PersistentVector::getEmpty();
        $vec = Coll::conj($vec, 'hello');
        $this->assertEquals('hello', $vec->nth(0));
    }


    function testFromSeq()
    {
        $vec = PersistentVector::ofColl(Coll::repeatn(100, 'foo'));
        $this->assertEquals(100, $vec->count());
        $this->assertTrue(Coll::every(function($x) { return $x == 'foo';}, $vec));
    }


    function testAssoc()
    {
        $arr = Core::threadl('foo')
                    ->pipe(Coll::$repeatn, [100])
                    ->pipe(Coll::$arr, [])
                    ->val();
        
        $vec = PersistentVector::ofColl($arr);
        $vec2 = $vec->assoc(56, 'bar');
        $this->assertEquals('bar', $vec2->nth(56));
        $this->assertEquals(Coll::arr($vec), $arr);
    }
}