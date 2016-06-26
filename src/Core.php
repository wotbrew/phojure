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
        $coll = self::seq($coll);
        if($coll != null)
            return $coll->cons($x);

        return new PersistentList($x, $coll);
    }

    public static function plist()
    {
        $args = func_get_args();
        return PersistentList::ofArray($args);
    }

    private static function seqFrom($coll){
        if($coll instanceof Seqable)
            return $coll->seq();
        else if ($coll === null)
            return null;
        else if ($coll instanceof \Iterator)
            return new IteratorSeq($coll);
        else if ($coll instanceof \IteratorAggregate)
            return new IteratorSeq($coll->getIterator());
        else if (is_array($coll)){
            if(count($coll) > 0){
                return self::seq(new \ArrayIterator($coll));
            }
            return null;
        }

        throw new \Exception("Cannot find seq");
    }

    public static function seq($coll){
        if($coll instanceof ASeq) return $coll;
        else if ($coll instanceof LazySeq)
            return $coll->seq();

        return self::seqFrom($coll);
    }

    static $first = 'phojure\\Core::first';
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

    static $last = 'phojure\\Core::last';
    public static function last($coll){
        $a = $coll;
        while(true){
            $x = self::seq(self::rest($a));
            if($x == null){
                return self::first($a);
            }
            $a = $x;
        }
        return null;
    }


    public static function seqIterator($coll){
        return new SeqIterator(self::seq($coll));
    }

    static $map = 'phojure\\Core::map';
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
        if($n <= 0) return null;

        return new LazySeq(function() use ($n, $coll){
            return self::cons(
                self::first($coll),
                self::take($n - 1, self::rest($coll))
            );
        });
    }

    public static function drop($n, $coll){
        if(!$coll) return null;
        if($n <= 0) return $coll;
        return new LazySeq(function() use ($n, $coll){
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

    public static function threadf($x){
        return new ThreadFirst($x);
    }
    public static function threadl($x){
        return new ThreadLast($x);
    }
}