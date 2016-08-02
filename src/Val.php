<?php

namespace phojure;

class Val
{
    static $threadf = 'phojure\\Val::threadf';

    static function threadf($x)
    {
        return new ThreadFirst($x);
    }

    static $threadl = 'phojure\\Val::threadl';

    static function threadl($x)
    {
        return new ThreadLast($x);
    }

    static $eq = "phojure\\Val::eq";

    static function eq($a, $b)
    {
        if ($a === $b) return true;
        if ($a != null) {
            if ($a instanceof IEq) {
                return $a->eq($b);
            } elseif ($b instanceof IEq) {
                return $b->eq($a);
            }
        }

        return false;
    }

    static $hash = "phojure\\Val::hash";

    static function hash($o)
    {
        if ($o == null) return 0;
        if ($o instanceof IHashEq) return $o->hasheq();

        return 0;
    }

    static $isNil = 'phojure\\Val::isNil';

    public static function isNil($x)
    {
        return $x === null;
    }

    static $some = 'phojure\\Val::some';

    public static function some($x)
    {
        return $x !== null;
    }
}