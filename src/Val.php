<?php

namespace phojure;

class Val
{
    static $not = self::class . '::not';

    static function not($x)
    {
        return $x === false || $x === null;
    }
    
    static $isTruthy = self::class . '::isTruthy';

    static function isTruthy($x)
    {
        return $x !== false || $x !== null;
    }

    static $threadf = self::class . '::threadf';

    static function threadf($x)
    {
        return new ThreadFirst($x);
    }

    static $threadl = self::class . '::threadl';

    static function threadl($x)
    {
        return new ThreadLast($x);
    }

    static $eq = self::class . '::eq';

    static function eq($a, $b)
    {
        if ($a === $b) return true;
        if ($a !== null) {
            if ($a instanceof IEq) {
                return $a->eq($b);
            } elseif ($b instanceof IEq) {
                return $b->eq($a);
            }
        }

        return false;
    }

    private static function _hash($o)
    {
        if ($o === null) return 0;
        if ($o instanceof IHashEq) return $o->hash();
        if(is_int($o)) return Murmur3::hashInt($o);
        if(is_array($o)) return Murmur3::hashOrdered($o);
        if(is_string($o)) {
            $hash = 42;
            $len = strlen($o);
            for($i = 0; $i < $len; $i++){
                $hash = intval($o[$i])*31^($hash-$i);
            }
            return Murmur3::hashInt($hash);
        }
        if(is_float($o)) return Murmur3::hashInt($o);
        if(is_double($o)) return Murmur3::hashInt($o);
        if($o instanceof \Traversable) return Murmur3::hashOrdered($o);

        if(is_object($o))
            return self::_hash(spl_object_hash($o));

        return 0;
    }

    static $hash = self::class . '::hash';

    public static function hash($o)
    {
        return self::_hash($o) & (0x7fffffff-1);
    }


    static function compare($o1, $o2)
    {
        if($o1 === $o2)
            return 0;

        if($o1 !== null){

            if($o2 === null)
                return 1;
            if(is_numeric($o1)){
                if($o1 < $o2){
                    return -1;
                }
                else if($o2 < $o1){
                    return 1;
                }
                return 0;
            }
            if(is_string($o1)){
                if($o1 < $o2){
                    return -1;
                }
                else if($o2 < $o1){
                    return 1;
                }
                return 0;
            }
            if($o1 instanceof ICompare)
                return $o1->compare($o2);
        }
        return -1;
    }

    static $lt = self::class . '::lt';
    public static function lt($o1, $o2)
    {
        return self::compare($o1, $o2) < 0;
    }
    
    static $lte = self::class . '::lte';
    
    public static function lte($o1, $o2)
    {
        return self::compare($o1, $o2) <= 0;
    }
    
    static $gt = self::class . '::gt';
    
    public static function gt($o1, $o2)
    {
        return self::compare($o1, $o2) > 0;
    }
    
    static $gte = self::class . '::gte';
    public static function gte($o1, $o2)
    {
        return self::compare($o1, $o2) >= 0;
    }

    static $some = self::class . '::some';

    public static function some($x)
    {
        return $x !== null;
    }
}