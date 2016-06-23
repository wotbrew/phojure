<?php
/**
 * Created by PhpStorm.
 * User: danielstone
 * Date: 22/06/2016
 * Time: 14:27
 */

namespace phojure;

class Cons extends ASeq
{
    private $head;
    private $tail;

    /**
     * Cons constructor.
     * @param $head
     * @param $tail
     */
    public function __construct($head, $tail)
    {
        $this->head = $head;
        $this->tail = $tail;
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