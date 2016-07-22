<?php

namespace phojure;

class Core
{
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
        return false;
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
}