<?php


namespace phojure;


class Coll
{
    static $cons = 'phojure\\Coll::cons';

    static function cons($x, $coll)
    {
        if ($coll == null)
            return new PersistentList($x, null);
        else if ($coll instanceof ISeq) {
            return new Cons($x, $coll);
        } else {
            return new Cons($x, self::seq($coll));
        }
    }

    static $conj = 'phojure\\Coll::conj';

    static function conj($coll, $x)
    {
        if ($coll == null)
            return new PersistentList($x, null);

        return $coll->cons($x);
    }

    static $plist = 'phojure\\Coll::plist';

    static function plist()
    {
        $args = func_get_args();
        return PersistentList::ofArray($args);
    }

    private static function seqFrom($coll)
    {
        if ($coll instanceof Seqable)
            return $coll->seq();
        else if ($coll === null)
            return null;
        else if ($coll instanceof \Iterator)
            return new IteratorSeq($coll);
        else if ($coll instanceof \IteratorAggregate)
            return new IteratorSeq($coll->getIterator());
        else if (is_array($coll)) {
            if (count($coll) > 0) {
                return self::seq(new \ArrayIterator($coll));
            }
            return null;
        }

        throw new \Exception("Cannot find seq");
    }

    static $seq = 'phojure\\Coll::seq';

    static function seq($coll)
    {
        if ($coll instanceof ASeq) return $coll;
        else if ($coll instanceof UncachedLazySeq)
            return $coll->seq();

        return self::seqFrom($coll);
    }

    static $first = 'phojure\\Coll::first';

    static function first($coll)
    {
        $s = self::seq($coll);
        if ($s) {
            return $s->first();
        }
        return null;
    }

    static $ffirst = 'phojure\\Coll::ffirst';

    static function ffirst($coll)
    {
        return self::first(self::first($coll));
    }

    static $nfirst = 'phojure\\Coll::nfirst';

    static function nfirst($coll)
    {
        return self::next(self::first($coll));
    }

    static $nnext = 'phojure\\Coll::nnext';

    static function nnext($coll)
    {
        return self::next(self::next($coll));
    }

    static $fnext = 'phojure\\Coll::fnext';

    static function fnext($coll)
    {
        return self::first(self::next($coll));
    }

    static $next = 'phojure\\Coll::next';

    static function next($coll)
    {
        $s = self::seq($coll);
        if ($s) {
            return $s->next();
        }
        return null;
    }

    static $rest = 'phojure\\Coll::rest';

    static function rest($coll)
    {
        $s = self::seq($coll);
        if ($s) {
            return $s->rest();
        }
        return null;
    }

    static $is_seq = 'phojure\\Coll::is_seq';

    static function is_seq($coll)
    {
        return $coll instanceof ISeq;
    }


    static $last = 'phojure\\Coll::last';

    static function last($coll)
    {
        $a = $coll;
        while (true) {
            $x = self::seq(self::rest($a));
            if ($x == null) {
                return self::first($a);
            }
            $a = $x;
        }
        return null;
    }


    static function seqIterator($coll)
    {
        return new SeqIterator(self::seq($coll));
    }

    static $map = 'phojure\\Coll::map';

    static function map($f, $coll)
    {
        if (!$coll) return null;
        return new UncachedLazySeq(function () use ($f, $coll) {
            return self::cons(
                $f(self::first($coll)),
                self::map($f, self::rest($coll)));
        });
    }

    static $take = 'phojure\\Coll::take';

    static function take($n, $coll)
    {
        if (!$coll) return null;
        if ($n <= 0) return null;

        return new UncachedLazySeq(function () use ($n, $coll) {
            return self::cons(
                self::first($coll),
                self::take($n - 1, self::rest($coll))
            );
        });
    }

    static $drop = 'phojure\\Coll::drop';

    static function drop($n, $coll)
    {
        if (!$coll) return null;
        if ($n <= 0) return $coll;
        return new UncachedLazySeq(function () use ($n, $coll) {
            return self::drop($n - 1, $coll);
        });
    }

    static $repeat = 'phojure\\Coll::repeat';

    static function repeat($x)
    {
        return new UncachedLazySeq(function () use ($x) {
            return self::cons(
                $x,
                self::repeat($x)
            );
        });
    }

    static $repeatn = 'phojure\\Coll::repeatn';
    static function repeatn($n, $x){
        return self::take($n, self::repeat($x));
    }
    
    static $every = 'phojure\\Coll::every';
    static function every($pred, $coll)
    {
        $s = self::seq($coll);
        for(; $s != null; $s = $s->next()){
            if(!$pred($s->first())){
                return false;
            }
        }
        return true;
    }

    static $arr = 'phojure\\Coll::arr';
    static function arr($coll){
        $s = self::seq($coll);
        $ret = array();
        for(; $s != null; $s = $s->next()){
            array_push($ret, $s->first());
        }
        return $ret;
    }
}