<?php


namespace phojure;


class Util
{

    static $uRShift = "phojure\\Util::urShift";

    static function uRShift($a, $b)
    {
        if ($b == 0) return $a;
        return ($a >> $b) & ~(1 << (8 * PHP_INT_SIZE - 1) >> ($b - 1));
    }

    static $splArrayCopy = "phojure\\Util::splArrayCopy";

    static function splArrayCopy(\SplFixedArray $a, int $aoffset,
                                 \SplFixedArray $b, int $boffset,
                                 int $length)
    {
        for ($i = 0; $i < $length; $i++) {
            $b[$i + $boffset] = $a[$aoffset + $i];
        }
    }
    
}