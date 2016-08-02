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
        $arr = Val::threadl('foo')
                    ->pipe(Coll::$repeatn, 100)
                    ->pipe(Coll::$arr)
                    ->deref();
        
        $vec = Coll::vec($arr);
        $vec2 = $vec->assoc(56, 'bar');
        $this->assertEquals('bar', $vec2->nth(56));
        $this->assertEquals(Coll::arr($vec), $arr);
    }

    function testReduce()
    {
        $n = Coll::reduce(Math::$add, 0, Coll::vec(Coll::repeatn(10, 1)));
        $this->assertEquals(10, $n);
    }

    function testReduceKV()
    {
        $this->assertTrue(
            Val::eq(
                Coll::vector(1,2,3),
                Coll::reduceKv(Map::$assoc, Coll::vector(), Coll::vector(1, 2, 3))
            )
        );
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
    function testEq(){
        $vec = Coll::vec([1, 2, 3]);
        $this->assertTrue(Val::eq($vec, $vec));
        $this->assertTrue(Val::eq($vec, Coll::vec([1,2,3])));
        $this->assertFalse(Val::eq($vec, Coll::vec([1,3,2])));
        $this->assertTrue(Val::eq($vec, Coll::lst(1,2,3)));
        $this->assertTrue(Val::eq($vec, Coll::range(1, 4)));
        $this->assertFalse(Val::eq($vec, Coll::lst(1,2,3,4)));
    }
}