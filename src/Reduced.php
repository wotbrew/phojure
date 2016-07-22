<?php


namespace phojure;


class Reduced implements IDeref
{
    private $val;

    public function __construct($val)
    {
        $this->val = $val;
    }

    function deref()
    {
        return $this->val;
    }
}