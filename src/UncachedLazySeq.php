<?php

namespace phojure;

class UncachedLazySeq extends ASeq
{
    private $f;

    /**
     * LazySeq constructor.
     * @param $f
     */
    public function __construct($f)
    {
        $this->f = $f;
    }

    function sval(){
        return call_user_func($this->f);
    }

    function seq(){
        $r = $this->sval();
        while($r instanceof UncachedLazySeq){
            $r = $r->sval();
        }
        return $r;
    }

    function first()
    {
        $s = $this->seq();
        if($s) return $s->first();
        return null;
    }

    function next()
    {
        $x = $this->seq();
        if($x != null) return $x->next();
        return null;
    }

    function nothing()
    {
        return EmptyList::get();
    }

    function getIterator()
    {
        return new SeqIterator($this->seq());
    }
}