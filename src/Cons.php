<?php

namespace phojure;

class Cons extends ASeq
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
        return new Cons($x, $this);
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