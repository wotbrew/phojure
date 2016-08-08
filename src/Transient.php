<?php


namespace phojure;


class Transient
{
    static $conj = self::class . '::conj';
    static function conj(ITransientCollection $coll, $x)
    {
        return $coll->conj($x);
    }

    static $persistent = self::class . '::persistent';
    static function persistent(ITransientCollection $coll)
    {
        return $coll->persistent();
    }
}