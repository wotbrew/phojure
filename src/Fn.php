<?php


namespace phojure;


class Fn
{

    static $call = 'phojure\\Fn::call';

    static function call($f, ... $args)
    {
        return call_user_func_array($f, $args);
    }

    static $apply = 'phojure\\Fn::apply';

    static function apply($f, ... $args)
    {
        $c = count($args);
        if ($c == 0) {
            return call_user_func($f);
        }
        $last = array_pop($args);
        return new ThreadFirst(call_user_func_array($f, $args + $last));
    }

    static $key = 'phojure\\Fn::key';

    static function key($k)
    {
        return function($m, $notFound = null) use ($k){
            return Map::get($m, $k, $notFound);
        };
    }

    static $juxt = 'phojure\\Fn::juxt';

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