<?php


namespace phojure;


class Math
{

    static $add = "phojure\\Math::add";

    static function add($a, $b)
    {
        return $a + $b;
    }

    static $sub = "phojure\\Math::sub";

    static function sub($a, $b)
    {
        return $a - $b;
    }

    static $mult = "phojure\\Math::mult";

    static function mult($a, $b)
    {
        return $a * $b;
    }

    static $div = "phojure\\Math::div";

    static function div($a, $b)
    {
        return $a / $b;
    }

    static $inc = "phojure\\Math::inc";

    static function inc($n)
    {
        return self::add($n, 1);
    }

    static $dec = "phojure\\Math::dec";

    static function dec($n)
    {
        return self::sub($n, 1);
    }
}