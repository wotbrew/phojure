<?php

namespace phojure;


class ThreadFirst implements IDeref
{
    private $val;

    /**
     * ThreadFirst constructor.
     * @param $val
     */
    public function __construct($val)
    {
        $this->val = $val;
    }

    function _($f, ... $arguments)
    {
        return new ThreadFirst(Fn::apply($f, $this->val, $arguments));
    }
    
    function deref()
    {
        return $this->val;
    }
}