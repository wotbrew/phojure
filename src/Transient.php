<?php


namespace phojure;


class Transient
{
    static $conj = self::class . '::conj';
    static function conj(ITransientCollection $coll, $x, ... $xs)
    {
        if($xs){
            $t = self::conj($coll, $x);
            foreach($xs as $x){
                $t = self::conj($t, $x);
            }
            return $t;
        }

        return $coll->conj($x);
    }

    static $assoc = self::class . '::assoc';
    static function assoc(ITransientMap $m, $k, $v, ... $kvs)
    {
        if($kvs){
            $t = self::assoc($m, $k, $v);
            for($i=0;$i<count($kvs);$i+=2){
                $t = self::assoc($t, $kvs[$i], $kvs[$i+2]);
            }
            return $t;
        }
        return $m->assoc($k, $v);
    }

    static $dissoc = self::class . '::dissoc';
    static function dissoc(ITransientMap $m, $k, ... $ks)
    {
        if($ks){
            $t = self::dissoc($m, $k);
            foreach($ks as $k){
                $t = self::dissoc($t, $k);
            }
            return $t;
        }
        
        return $m->dissoc($k);
    }

    static $persistent = self::class . '::persistent';
    static function persistent(ITransientCollection $coll)
    {
        return $coll->persistent();
    }
}