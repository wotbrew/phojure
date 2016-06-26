<?php

namespace phojure;

abstract class ASeq implements ISeq, Seqable, Sequential, IPersistentCollection, \IteratorAggregate, \Countable
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

    function nothing(){
        return EmptyList::get();
    }
}