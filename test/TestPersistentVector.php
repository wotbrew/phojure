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
}