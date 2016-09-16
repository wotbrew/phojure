<?php

namespace phojure;


class ThreadLast implements IDeref
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
        array_push($arguments, $this->val);
        return new ThreadLast(call_user_func_array($f, $arguments));
    }

    function deref()
    {
        return $this->val;
    }
}