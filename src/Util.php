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

    static $arrayCopy = "phojure\\Util::arrayCopy";

    static function arrayCopy(array &$a, int $aoffset, array &$b, int $boffset, int $length)
    {
        if($a === $b){
            $tmp = array_slice($a, $aoffset, $length);
            for ($i = 0; $i < $length; $i++) {
                $b[$i + $boffset] = $tmp[$i];
            }
        }
        else {
            for ($i = 0; $i < $length; $i++) {
                $b[$i + $boffset] = $a[$aoffset + $i];
            }
        }
    }


    static function bitCount($i)
    {
        $i = $i - (Util::uRShift($i,1) & 0x55555555);
        $i = ($i & 0x33333333) + (Util::uRShift($i, 2) & 0x33333333);
        $i = ($i + Util::uRShift($i, 4)) & 0x0f0f0f0f;
        $i = $i + Util::uRShift($i, 8);
        $i = $i + Util::uRShift($i, 16);
        return $i & 0x3f;
    }

}