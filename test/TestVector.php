<?php


namespace phojure;


class TestVector extends \PHPUnit_Framework_TestCase
{

    function testAdd()
    {
        $vec = PersistentVector::getEmpty();
        $vec = Coll::conj($vec, 'hello');
        $this->assertEquals('hello', $vec->nth(0));
    }


    function testFromSeq()
    {
        $vec = Coll::vec(Coll::repeatn(100, 'foo'));
        $this->assertEquals(100, $vec->count());
        $this->assertTrue(Coll::every(function($x) { return $x == 'foo';}, $vec));
    }


    function testAssoc()
    {
        $arr = Core::threadl('foo')
                    ->pipe(Coll::$repeatn, 100)
                    ->pipe(Coll::$arr)
                    ->val();
        
        $vec = Coll::vec($arr);
        $vec2 = $vec->assoc(56, 'bar');
        $this->assertEquals('bar', $vec2->nth(56));
        $this->assertEquals(Coll::arr($vec), $arr);
    }

    function testReduce()
    {
        $n = Coll::reduce(Core::$add, 0, Coll::vec(Coll::repeatn(10, 1)));
        $this->assertEquals(10, $n);
    }

    function testReduceKV()
    {

    }

    function testPeek(){
        $vec = Coll::vec([1, 2, 3, 4, 5]);
        $this->assertEquals(5, Coll::peek($vec));
    }

    function testPop(){
        $vec = Coll::vec([1, 2, 3, 4, 5]);
        $this->assertEquals([1, 2, 3, 4], Coll::arr(Coll::pop($vec)));
    }
    function testInvoke(){
        $vec = Coll::vec([1, 2, 3, 4, 5]);
        $this->assertEquals(1, $vec(0));
    }
    function testIndexAccess(){
        $vec = Coll::vec([1, 2, 3, 4, 5]);
        $this->assertEquals(1, $vec[0]);
        $this->assertEquals(2, $vec[1]);
        $this->assertEquals(3, $vec[2]);
        $this->assertEquals(4, $vec[3]);
    }
}