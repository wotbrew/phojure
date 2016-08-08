<?php

namespace phojure;

abstract class ASeq implements ISeq, Seqable, Sequential,
    IPersistentCollection, \IteratorAggregate, \Countable,
    IHashEq
{

    private $hash = -1;

    function hash()
    {
        if($this->hash === -1){
            $n = 0;
            $hash = 1;
            for($seq=$this->seq(); $seq !== null; $seq = $seq->next()){
                $hash = 31 * $hash + Val::hash($seq->first());
                ++$n;
            }
            $this->hash = Murmur3::mixCollHash($hash, $n);
        }
        return $this->hash;
    }

    public function getIterator()
    {
        return new SeqIterator($this->seq());
    }

    function rest()
    {
        $s = $this->next();
        if ($s === null) return $this->nothing();
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

    function count()
    {
        $i = 0;
        foreach ($this as $x) {
            $i++;
        }
        return $i;
    }

    function nothing()
    {
        return EmptyList::get();
    }

    function eq($a)
    {
        if (!($a instanceof Sequential
            || $a instanceof \Iterator
            || $a instanceof \IteratorAggregate
            || is_array($a))
        ) {
            return  false;
        }

        $ms = Coll::seq($a);
        for ($s = $this->seq(); $s !== null; $s = $s->next(), $ms = $ms->next()) {
            if ($ms === null || !Val::eq($s->first(), $ms->first()))
                return false;
        }
        return $ms === null && $s === null;
    }
}