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

    function pipe($name, ... $arguments)
    {
        array_unshift($arguments, $this->val);
        return new ThreadFirst(call_user_func_array($name, $arguments));
    }
    
    function deref()
    {
        return $this->val;
    }
}