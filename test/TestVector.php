<?php


namespace phojure;


use Lavoiesl\PhpBenchmark\Benchmark;

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

    function testHash(){
        $vec = Coll::vec([1, 2, 3]);
        $this->assertEquals(Val::hash($vec), Val::hash($vec));
        $this->assertEquals(Val::hash($vec), Val::hash(Coll::vector(1,2,3)));
        $this->assertEquals(Val::hash($vec), Val::hash(Coll::lst(1,2,3)));

        $this->assertNotEquals(Val::hash(Coll::vector()), Val::hash(Coll::vector(1,2,3)));
    }

    function testBench()
    {
        $bench = new Benchmark();
        $bench->setCount(100);

        $bench->add('vector-build-conj-100', function(){
            $vec = Coll::vector();
            for($i = 0; $i < 100; $i++){
                $vec = Coll::conj($vec, $i);
            }
        });

        $bench->add('vector-build-transient-conj-100', function(){
           $vec = Coll::transient(Coll::vector());
           for($i = 0; $i < 100; $i++){
               $vec = Transient::conj($vec, $i);
           }
           $vec = Transient::persistent($vec); 
        });

        $bench->add('array-build-push-100', function(){
            $arr = [];
            for($i = 0; $i < 100; $i++){
               array_push($arr, $i);
            }
        });


        $bench->run();
    }
}