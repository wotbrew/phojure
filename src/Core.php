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
}