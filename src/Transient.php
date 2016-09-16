<?php


namespace phojure;


class Transient
{
    static $conj = self::class . '::conj';
    static function conj(ITransientCollection $coll, $x)
    {
        return $coll->conj($x);
    }

    static $assoc = self::class . '::assoc';
    static function assoc(ITransientMap $m, $k, $v)
    {
        return $m->assoc($k, $v);
    }

    static $dissoc = self::class . '::dissoc';
    static function dissoc(ITransientMap $m, $k)
    {
        return $m->without($k);
    }

    static $persistent = self::class . '::persistent';
    static function persistent(ITransientCollection $coll)
    {
        return $coll->persistent();
    }
}