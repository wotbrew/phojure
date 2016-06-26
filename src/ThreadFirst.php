<?php
/**
 * Created by PhpStorm.
 * User: danielstone
 * Date: 23/06/2016
 * Time: 15:16
 */

namespace phojure;


class ThreadFirst
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

    function pipe($name, $arguments)
    {
        array_unshift($arguments, $this->val);
        return new ThreadFirst(call_user_func_array($name, $arguments));
    }

    function val(){
        return $this->val;
    }


}