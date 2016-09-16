<?php


namespace phojure;


class Fn
{

    static $call = self::class . '::call';

    static function call($f, ... $args)
    {
        return call_user_func_array($f, $args);
    }

    static $apply = self::class . '::apply';

    static function apply($f, ... $args)
    {
        if (empty($args)) {
            return call_user_func($f);
        }

        $l = array_pop($args);
        return call_user_func_array($f, array_merge($args, $l));
    }

    static $key = self::class . '::key';

    static function key($k)
    {
        return function($m, $notFound = null) use ($k){
            return Map::get($m, $k, $notFound);
        };
    }

    static $juxt = self::class . '::juxt';

    static function juxt(... $fs)
    {
        return function($x) use ($fs) {
          return array_map(
              function ($f) use ($x) {
                  return call_user_func($f, $x);},
              $fs);
        };
    }

}