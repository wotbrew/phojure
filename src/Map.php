<?php


namespace phojure;


class Map
{
    static $get = 'phojure\\get';

    static function get($m, $k, $notFound = null)
    {
        if($m == null) return $notFound;

        if ($m instanceof Associative){
            $m->valAtOr($k, $notFound);
        }
        if($m instanceof \ArrayAccess){
            if($m->offsetExists($k)){
                return $m[$k];
            }
        }
        if(is_array($m)){
            if(array_key_exists($k, $m)){
                return $m[$k];
            }
        }
        return $notFound;
    }

    static $getIn = 'phojure\\getIn';

    static function getIn($m, $ks, $notFound = null)
    {
        if(is_array($ks)){
            $ret = $m;
            $c = count($ks);
            for($i = 0; $i < $c; $i++){
                $k = $ks[$i];
                if($i == $c - 1) {
                    return self::get($m, $k, $notFound);
                }
                $ret = self::get($ret, $k);
            }
            return $notFound;
        }
        else {
            $ret = $m;
            $ks = Coll::seq($ks);
            while($ks !== null){
                $k = Coll::first($ks);
                $next = Coll::next($ks);
                if($next){
                    $ret = self::get($ret, $k);
                }
                else {
                    return self::get($ret, $k, $notFound);
                }
            }
            return $notFound;
        }

    }

    static $lookup = 'phojure\\Map::lookup';

    static function lookup($m, ... $ks)
    {
        return self::getIn($m, $ks);
    }

    static $assoc = 'phojure\\Map::assoc';

    static function assoc($m, $k, $v)
    {
        if($m != null)
            return $m->assoc($k, $v);

        // todo create map
        return null;
    }

    static $pull = 'phojure\\Map::pull';

    static function pull($m, ... $ks)
    {
        if($m === null)
            return null;

        return Coll::keep(function($k) use ($m) { return Map::get($m, $k); }, $ks);
    }

}