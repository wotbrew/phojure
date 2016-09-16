<?php


namespace phojure;


class Map
{
    static $contains = self::class . '::contains';

    static $get = self::class . '::get';

    static function get($m, $k, $notFound = null)
    {
        if($m == null) return $notFound;

        if ($m instanceof Associative){
            return $m->valAtOr($k, $notFound);
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

    static $getIn = self::class . '::getIn';

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

    static $lookup = self::class . '::lookup';

    static function lookup($m, ... $ks)
    {
        return self::getIn($m, $ks);
    }

    static $assoc = self::class . '::assoc';

    static function assoc($m, $k, $v)
    {
        if($m instanceof Associative){
            return $m->assoc($k, $v);
        }
        if($m === null){
            return PersistentArrayMap::getEmpty()->assoc($k, $v);
        }
        if(is_array($m)){
            $m[$k] = $v;
            return $m;
        }
        $class = get_class($m);
        throw new \Exception("Cannot assoc on: $class");
    }

    static $assocIn = self::class . '::assocIn';

    static function assocIn($m, $ks, $v)
    {
        //todo impl
    }

    static $dissoc = self::class . '::dissoc';

    static function dissoc($m, $k)
    {
        if($m instanceof IPersistentMap){
            return $m->without($k);
        }
        if(is_array($m)){
            if(array_key_exists($k, $m)){
                unset($m[$k]);
                return $m;
            }
            return $m;
        }
        return $m;
    }

    static $dissocIn = self::class . '::dissocIn';

    static function dissocIn($m, $ks)
    {
        //todo impl
    }

    static $of = self::class . '::of';

    static function of(... $kvs){
        if(count($kvs) < 16){
            return PersistentArrayMap::ofSeqArray($kvs);
        }
        return PersistentHashMap::ofSequentialArray($kvs);
    }

}