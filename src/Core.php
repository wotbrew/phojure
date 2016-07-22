<?php

namespace phojure;

class Core
{
    static $add = 'phojure\\Core::add';
    static function add($a, $b){
        return $a + $b;
    }

    static $threadf = 'phojure\\Core::threadf';

    static function threadf($x)
    {
        return new ThreadFirst($x);
    }

    static $threadl = 'phojure\\Core::threadl';

    static function threadl($x)
    {
        return new ThreadLast($x);
    }

    static $is_reduced = 'phojure\\Core::is_reduced';

    static function is_reduced($x)
    {
        return $x instanceof Reduced;
    }
    
    static $apply = 'phojure\\Core::apply';
    
    static function apply($f, ... $args) 
    {
        $c = count($args);
        if($c == 0){
            return call_user_func($f);
        }
        $last = Coll::arr($args[$c - 1]);
        $arr = [];
        for($i = 0; $i < $c - 1; $i++){
            array_push($arr, $args[$i]);
        }
        for($i = 0; $i < count($last); $i++){
            array_push($arr, $last[$i]);
        }
        return new ThreadFirst(call_user_func_array($f, $arr));
    }

    static $hash = 'phojure\\Core::hash';

    static function hash($x)
    {
        if ($x == null) return 0;
        return 1;
    }
}