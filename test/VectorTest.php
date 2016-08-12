<?php


namespace phojure;


use Lavoiesl\PhpBenchmark\Benchmark;

class VectorTest extends \PHPUnit_Framework_TestCase
{

    function testAdd()
    {
        $vec1 = PersistentVector::getEmpty();
        $vec2 = Coll::conj($vec1, 'hello');
        $this->assertEquals(Coll::vector(), $vec1);
        $this->assertTrue(Val::eq($vec1, Coll::lst()));
        $this->assertEquals('hello', $vec2->nth(0));
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
        $this->assertNotEquals($vec, $vec2);
        $this->assertEquals('bar', $vec2->nth(56));

        $this->assertEquals($arr, Coll::arr($vec));
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

    function testCompare(){
        $this->assertTrue(Val::lt(Coll::vector('a'), Coll::vector('b')));
        $this->assertFalse(Val::gt(Coll::vector('a'), Coll::vector('b')));
        $this->assertTrue(Val::gt(Coll::vector('z', 'a'), Coll::vector('b')));
        $this->assertTrue(Val::lt(Coll::vector('z', 'a'), Coll::vector('z', 'b')));
        $this->assertEquals(0, Val::compare(Coll::vector(1,2,3), Coll::vector(1,2,3)));
    }

    function testBenchBuild()
    {

        $bench = new Benchmark();
        $bench->setCount(20);

        $arr = range(0, 1000);
        $seq = Coll::seq($arr);
        $bench->add('vector-build-conj', function(){
            $vec = Coll::vector();
            for($i = 0; $i < 1000; $i++){
                $vec = Coll::conj($vec, $i);
            }
        });

        $bench->add('vector-build-transient-conj', function(){
           $vec = Coll::transient(Coll::vector());
           for($i = 0; $i < 1000; $i++){
               $vec = Transient::conj($vec, $i);
           }
           $vec = Transient::persistent($vec); 
        });

        $bench->add('vector-build-from-array', function() use ($arr){
            $vec = Coll::vec($arr);
        });
        $bench->add('vector-build-from-seq', function() use ($seq){
            $vec = Coll::vec($seq);
        });

        $bench->add('array-build-push', function(){
            $arr = [];
            for($i = 0; $i < 1000; $i++){
               array_push($arr, $i);
            }
        });

        $bench->run();
    }

    function testBenchNthSequential()
    {

        $bench = new Benchmark();
        $bench->setCount(10);
        $arr = range(0, 1000);

        $vec = Coll::vec($arr);
        $vect = Coll::transient($vec);

        $bench->add('vector-nth-sequential', function() use ($vec){
            for($i = 0; $i < 1000; $i++){
                Coll::nth($vec, $i);
            }
        });

        $bench->add('vector-nth-transient-sequential', function() use ($vect){
            for($i = 0; $i < 1000; $i++){
                Coll::nth($vect, $i);
            }
        });

        $bench->add('array-nth-sequential', function() use ($arr){
            for($i = 0; $i < 1000; $i++){
                Coll::nth($arr, $i);
            }
        });

        $bench->run();
    }

    function testBenchNthRandom()
    {

        $bench = new Benchmark();
        $bench->setCount(10);
        $arr = range(0, 1000);
        shuffle($arr);

        $vec = Coll::vec($arr);
        $vect = Coll::transient($vec);

        $bench->add('vector-nth-random', function() use ($vec){
            for($i = 0; $i < 1000; $i++){
                Coll::nth($vec, $i);
            }
        });

        $bench->add('vector-nth-transient-random', function() use ($vect){
            for($i = 0; $i < 1000; $i++){
                Coll::nth($vect, $i);
            }
        });

        $bench->add('array-nth-random', function() use ($arr){
            for($i = 0; $i < 1000; $i++){
                Coll::nth($arr, $i);
            }
        });

        $bench->run();
    }

    function testBenchAdd1000thElement()
    {

        $bench = new Benchmark();
        $bench->setCount(1000);
        $arr = range(0, 1000);
        shuffle($arr);

        $vec = Coll::vec($arr);
        $vect = Coll::transient($vec);

        $bench->add('vector-add-1000th', function() use ($vec){
            $vec = Coll::conj($vec, 'foobar!');
        });

        $bench->add('vector-transient-add-1000th', function() use ($vect){
            $vect = Transient::conj($vect, 'foobar!');
        });

        $bench->add('array-add-1000th', function() use ($arr){
            $arr2 = $arr;
            array_push($arr2, 'foobar!');
        });

        $bench->run();
    }

    function testBenchAdd10000thElement()
    {

        $bench = new Benchmark();

        $bench->setCount(100);
        $arr = range(0, 10000);
        shuffle($arr);

        $vec = Coll::vec($arr);
        $vect = Coll::transient($vec);

        $bench->add('vector-add-10000th', function() use ($vec){
            $vec = Coll::conj($vec, 'foobar!');
        });

        $bench->add('vector-transient-add-10000th', function() use ($vect){
            $vect = Transient::conj($vect, 'foobar!');
        });

        $bench->add('array-add-10000th', function() use ($arr){
            $arr2 = $arr;
            array_push($arr2, 'foobar!');
        });

        $bench->run();
    }
}