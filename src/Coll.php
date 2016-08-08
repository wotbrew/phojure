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

    static $lst = 'phojure\\Coll::lst';

    static function lst(... $xs)
    {
        return PersistentList::ofArray($xs);
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

    static $isSeq = 'phojure\\Coll::is_seq';

    static function isSeq($coll)
    {
        return $coll instanceof ISeq;
    }

    static function isSequential($coll)
    {
        return is_array($coll) || $coll instanceof Sequential;
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

    static $seqIterator = 'phojure\\Coll::seq_iterator';

    static function seqIterator($coll)
    {
        return new SeqIterator(self::seq($coll));
    }


    private static function naive_seq_reduce($coll, $f, $init)
    {
        $val = $init;
        for ($seq = self::seq($coll); $seq != null; $seq = self::next($seq)) {
            $val = call_user_func($f, $val, self::first($seq));
            if ($val instanceof Reduced) {
                return $val->deref();
            }
        }
        return $val;
    }

    static $peek = 'phojure\\Coll::peek';

    static function peek($coll)
    {
        return $coll->peek();
    }

    static $pop = 'phojure\\Coll::pop';

    static function pop($coll)
    {
        return $coll->pop();
    }

    static $reduce = 'phojure\\Coll:reduce';

    static function reduce($f, $init, $coll)
    {
        if ($coll instanceof IReduce) {
            return $coll->reduce($f, $init);
        }
        return self::naive_seq_reduce($coll, $f, $init);
    }

    static $reduceKv = 'phojure\\Coll:reduce_kv';

    static function reduceKv($f, $init, $coll)
    {
        if ($coll instanceof IKVReduce) {
            return $coll->reduceKV($f, $init);
        }

        $val = $init;
        $i = 0;
        for ($seq = self::seq($coll); $seq != null; $seq = self::next($seq)) {
            $val = call_user_func($f, $val, $i, self::first($seq));
            if ($val instanceof Reduced) {
                return $val->deref();
            }
            $i++;
        }
        return $val;
    }

    static $run = 'phojure\\Coll::run';

    static function run($f, $coll)
    {
        self::reduce(function ($_, $x) use ($f) {
            call_user_func($f, $x);
        }, null, $coll);
    }

    static $map = 'phojure\\Coll::map';

    static function map($f, $coll)
    {
        if (!$coll) return null;
        return new UncachedLazySeq(function () use ($f, $coll) {
            return self::cons(
                call_user_func($f, self::first($coll)),
                self::map($f, self::rest($coll)));
        });
    }

    static $filter = 'phojure\\Coll::filter';

    static function filter($pred, $coll)
    {
        if(!$coll) return null;
        return new UncachedLazySeq(function () use($pred, $coll) {
            if (call_user_func($pred, $coll)) {
                return self::cons(self::first($coll),
                    self::filter($pred, self::rest($coll)));
            }

            return self::filter($pred, self::rest($coll));
        });
    }

    static $keep = 'phojure\\Coll::keep';

    static function keep($f, $coll)
    {
        if(!$coll) return null;
        return new UncachedLazySeq(function() use($f, $coll) {
           $x = call_user_func($f, self::first($coll));
           if($x !== null){
               return self::cons(self::first($coll),
                   self::keep($f, self::rest($coll)));
           }
            return self::keep($f, self::rest($coll));
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
            $rest = Coll::rest($coll);
            if($rest !== null) {
                return self::drop($n - 1, $rest);
            }
            return null;
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

    static function repeatn($n, $x)
    {
        return self::take($n, self::repeat($x));
    }

    static $rangeBy = 'phojure\\Coll::rangeBy';

    static function rangeBy($start, $end, $step)
    {
        return new UncachedLazySeq(function () use ($start, $end, $step) {
            $nxt = $start + $step;
            return self::cons(
                $start,
                $nxt < $end ? self::rangeBy($nxt, $end, $step) : null
            );
        });
    }
    static $range = 'phojure\\Coll::range';

    static function range($start, $end)
    {
        return self::rangeBy($start, $end, 1);
    }


    static $every = 'phojure\\Coll::every';

    static function every($pred, $coll)
    {
        $s = self::seq($coll);
        for (; $s != null; $s = $s->next()) {
            if (!$pred($s->first())) {
                return false;
            }
        }
        return true;
    }

    static $arr = 'phojure\\Coll::arr';

    static function arr($coll)
    {
        if (is_array($coll)) return $coll;
        if ($coll == null) return [];

        $s = self::seq($coll);
        $ret = array();
        for (; $s != null; $s = $s->next()) {
            array_push($ret, $s->first());
        }
        return $ret;
    }

    static $vec = 'phojure\\Coll::vec';

    static function vec($coll)
    {
        return PersistentVector::ofColl($coll);
    }

    static $vector = 'phojure\\Coll::vector';

    static function vector(... $xs)
    {
        return self::vec($xs);
    }

    static $nth = self::class . '::nth';

    static function nth($coll, $i, $notFound = null)
    {
        if($coll instanceof Indexed){
            return $coll->nthOr($i, $notFound);
        }

        if(is_array($coll)){
            return Map::get($coll, $i, $notFound);
        }

        if($coll instanceof Sequential){
            return self::first(self::drop($i, $coll));
        }

        return $notFound;
    }

    static $transient = self::class . '::transient';

    static function transient($coll)
    {
        return $coll->asTransient();
    }

}