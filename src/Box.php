<?php


namespace phojure;

class EmptyBox implements IHashEq
{
    private static $id;
    private $hash;

    /**
     * EmptyBox constructor.
     */
    public function __construct()
    {
        $this->hash = Val::hash(self::$id);
        self::$id++;
    }


    function hash()
    {
        return $this->hash;
    }
}

class Box implements IHashEq
{
    private static $id;
    private $hash;

    public $val;

    public function __construct($val)
    {
        $this->hash = Val::hash(self::$id);
        self::$id++;
        $this->val = $val;
    }

    function hash()
    {
        return $this->hash;
    }
}