<?php

namespace phojure;

abstract class ASeq implements Seq, Seqable, PersistentCollection, \IteratorAggregate, \Countable
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
        return new PersistentList($x, $this);
    }

    function count(){
        $i = 0;
        foreach($this as $x){
            $i++;
        }
        return $i;
    }
}