<?php
/**
 * Created by PhpStorm.
 * User: danielstone
 * Date: 22/06/2016
 * Time: 20:58
 */

namespace phojure;


use Traversable;

abstract class ASeq implements Seq, Seqable, PersistentCollection, \IteratorAggregate
{
    public function getIterator()
    {
        return new SeqIterator($this->seq());
    }

    function rest()
    {
        $s = $this->next();
        if($s == null) return $this->nothing();
        return $s;
    }

    function seq()
    {
        return $this;
    }

    function cons($x)
    {
        return new Cons($x, $this);
    }
}