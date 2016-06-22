<?php
/**
 * Created by PhpStorm.
 * User: danielstone
 * Date: 22/06/2016
 * Time: 13:37
 */

namespace phojure;

class Core
{
    public static function cons($x, $coll){
        return new Cons($x, self::seq($coll));
    }

    public static function seq($coll){
        if (!$coll) return null;

        if ($coll instanceof Seq){
            return $coll;
        }
        else if ($coll instanceof Seqable){
            return $coll->seq();
        }
        if(is_array($coll)){
            if(count($coll) > 0){
                return self::seq(new \ArrayIterator($coll));
            }
            return null;
        }
        else if ($coll instanceof \Iterator){
            return new LazySeq(function() use ($coll) {
                $head = $coll->current();
                $coll->next();
                $tail = $coll->valid() ? self::seq($coll) : null;
                return new Cons($head, $tail);
            });
        }
        else if ($coll instanceof \IteratorAggregate){
            return self::seq($coll->getIterator());
        }

        throw new \Exception();
    }

    public static function first($coll){
        $s = self::seq($coll);
        if ($s){
            return $s->first();
        }
        return null;
    }

    public static function rest($coll){
        $s = self::seq($coll);
        if ($s){
            return $s->rest();
        }
        return null;
    }

    public static function seqIterator($coll){
        return new SeqIterator(self::seq($coll));
    }

    public static function map($f, $coll){
        if(!$coll) return null;
        return new LazySeq(function() use ($f, $coll) {
            return self::cons(
                $f(self::first($coll)),
                self::map($f, self::rest($coll)));
        });
    }

    public static function take($n, $coll){
        if(!$coll) return null;
        return new LazySeq(function() use ($n, $coll){
            if($n <= 0) return null;
            return self::cons(
                self::first($coll),
                self::take($n - 1, self::rest($coll))
            );
        });
    }

    public static function drop($n, $coll){
        if(!$coll) return null;
        return new LazySeq(function() use ($n, $coll){
            if($n <= 0) return $coll;
            return self::drop($n - 1, $coll);
        });
    }

    public static function repeat($x){
        return new LazySeq(function() use ($x) {
           return self::cons(
               $x,
               self::repeat($x)
           );
        });
    }
}