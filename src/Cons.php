<?php
/**
 * Created by PhpStorm.
 * User: danielstone
 * Date: 22/06/2016
 * Time: 14:27
 */

namespace phojure;

class Cons implements Seq, Seqable, \IteratorAggregate
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


    public function getIterator()
    {
        return new SeqIterator($this);
    }

    function first()
    {
        return $this->head;
    }

    function rest()
    {
        return $this->tail;
    }

    function seq()
    {
        return $this;
    }
}