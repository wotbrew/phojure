<?php


namespace phojure;


class Util
{
    static function uRShift($a, $b)
    {
        if($b == 0) return $a;
        return ($a >> $b) & ~(1<<(8*PHP_INT_SIZE-1)>>($b-1));
    }

    static function splArrayCopy($a, $aoffset, $b, $boffset, $length){
        for($i = 0; $i < $length; $i++){
            $b[$i + $boffset] = $a[$aoffset + $i];
        }
    }
}