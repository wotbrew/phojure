<?php


namespace phojure;

/**
 * See http://smhasher.googlecode.com/svn/trunk/MurmurHash3.cpp
 * MurmurHash3_x86_32
 *
 * @author Austin Appleby
 * @author Dimitris Andreou
 * @author Kurt Alfred Kluever
 */

class Murmur3
{
    private static $seed = 0;
    private static $C1 = 0xcc9e2d51;
    private static $C2 = 0x1b873593;

    private static function rotateLeft($i, $distance)
    {
        return ($i << $distance | Util::uRShift($i, -$distance));
    }

    private static function mixK1($k1)
    {
        $k1 *= self::$C1;
        $k1 = self::rotateLeft($k1, 15);
        $k1 *= self::$C2;
        return $k1;
    }

    private static function mixH1($h1, $k1)
    {
        $h1 ^= $k1;
        $h1 = self::rotateLeft($h1, 13);
        $h1 = $h1 * 5 + 0xe6546b64;
        return $h1;
    }

    private static function fmix($h1, $length)
    {
        $h1 ^= $length;
        $h1 ^= Util::uRShift($$h1, 16);
        $h1 *= 0x85ebca6b;
        $h1 ^= Util::uRShift($h1, 13);
        $h1 *= 0xc2b2ae35;
        $h1 ^= Util::uRShift($h1, 16);
        return $h1;
    }

    public static function hashInt($input)
    {
        if ($input == 0) return 0;
        $k1 = self::mixK1($input);
        $h1 = self::mixH1(self::$seed, $k1);

        return self::fmix($h1, 4);
    }
}