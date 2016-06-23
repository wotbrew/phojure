<?php

namespace phojure;

class EmptyList extends ASeq {
    public static function get(){
        static $x;
        if(!$x) {
            $x = new EmptyList();
        }
        return $x;
    }


    function nothing()
    {
        return self::get();
    }

    function first()
    {
        return null;
    }

    function next()
    {
        return null;
    }
}

class PersistentList extends ASeq
{
    private $head;
    private $tail;

    function __construct($head, $tail)
    {
        $this->head = $head;
        $this->tail = $tail;
    }

    function cons($x)
    {
        return new PersistentList($x, $this);
    }

    function nothing()
    {
        return EmptyList::get();
    }

    function first()
    {
        return $this->head;
    }

    function next()
    {
        return $this->tail;
    }
}