<?php

namespace phojure;

class Core
{
    static $cons = 'phojure\\Core::cons';
    static function cons($x, $coll){
        if($coll == null)
            return new PersistentList($x, null);
        else if ($coll instanceof ISeq){
            return new Cons($x, $coll);
        }
        else {
            return new Cons($x, self::seq($coll));
        }
    }

    static $conj = 'phojure\\Core::conj';
    static function conj($x, $coll){
        if($coll == null)
            return new PersistentList($x, null);

        return $coll->cons($x);
    }

    static $plist = 'phojure\\Core::plist';
    static function plist()
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

    static $seq = 'phojure\\Core::seq';
    static function seq($coll){
        if($coll instanceof ASeq) return $coll;
        else if ($coll instanceof LazySeq)
            return $coll->seq();

        return self::seqFrom($coll);
    }

    static $first = 'phojure\\Core::first';
    static function first($coll){
        $s = self::seq($coll);
        if ($s){
            return $s->first();
        }
        return null;
    }

    static $ffirst = 'phojure\\Core::ffirst';
    static function ffirst($coll){
        return self::first(self::first($coll));
    }

    static $nfirst = 'phojure\\Core::nfirst';
    static function nfirst($coll){
        return self::next(self::first($coll));
    }

    static $nnext = 'phojure\\Core::nnext';
    static function nnext($coll){
        return self::next(self::next($coll));
    }

    static $fnext = 'phojure\\Core::fnext';
    static function fnext($coll){
        return self::first(self::next($coll));
    }

    static $next = 'phojure\\Core::next';
    static function next($coll){
        $s = self::seq($coll);
        if ($s){
            return $s->next();
        }
        return null;
    }

    static $rest = 'phojure\\Core::rest';
    static function rest($coll){
        $s = self::seq($coll);
        if ($s){
            return $s->rest();
        }
        return null;
    }

    static $is_seq = 'phojure\\Core::is_seq';
    static function is_seq($coll){
        return $coll instanceof ISeq;
    }

    
    static $last = 'phojure\\Core::last';
    static function last($coll){
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


    static function seqIterator($coll){
        return new SeqIterator(self::seq($coll));
    }

    static $map = 'phojure\\Core::map';
    static function map($f, $coll){
        if(!$coll) return null;
        return new LazySeq(function() use ($f, $coll) {
            return self::cons(
                $f(self::first($coll)),
                self::map($f, self::rest($coll)));
        });
    }

    static $take = 'phojure\\Core::take';
    static function take($n, $coll){
        if(!$coll) return null;
        if($n <= 0) return null;

        return new LazySeq(function() use ($n, $coll){
            return self::cons(
                self::first($coll),
                self::take($n - 1, self::rest($coll))
            );
        });
    }

    static $drop = 'phojure\\Core::drop';
    static function drop($n, $coll){
        if(!$coll) return null;
        if($n <= 0) return $coll;
        return new LazySeq(function() use ($n, $coll){
            return self::drop($n - 1, $coll);
        });
    }

    static $repeat = 'phojure\\Core::repeat';
    static function repeat($x){
        return new LazySeq(function() use ($x) {
           return self::cons(
               $x,
               self::repeat($x)
           );
        });
    }

    static $threadf = 'phojure\\Core::threadf';
    static function threadf($x){
        return new ThreadFirst($x);
    }

    static $threadl = 'phojure\\Core::threadl';
    static function threadl($x){
        return new ThreadLast($x);
    }
}