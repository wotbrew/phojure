<?php
/**
 * Created by PhpStorm.
 * User: danielstone
 * Date: 22/06/2016
 * Time: 13:37
 */

namespace phojure;

class LazySeq extends ASeq
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
        while($r instanceof LazySeq){
            $r = $r->sval();
        }
        return $r;
    }

    function first()
    {
        return Core::first($this->seq());
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