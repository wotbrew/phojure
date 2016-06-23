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
    private $sval;
    private $s;

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
        if($this->f != null){
            $this->sval = call_user_func($this->f);
            $this->f = null;
        }
        if ($this->sval != null){
            return $this->sval;
        }
        return $this->s;
    }

    function seq(){
        $r = $this->sval();
        while($r instanceof LazySeq){
            $r = $r->sval();
        }
        return $r;

        $this->sval();
        if($this->sval != null){
            $ls = $this->sval;
            $this->sval = null;
            while ($ls instanceof LazySeq){
                $ls = $ls->sval();
            }
            $this->s = Core::seq($ls);
            return $this->s;
        }
        return null;
    }

    function first()
    {
        return Core::first($this->seq());
        $this->seq();
        if ($this->s == null){
            return null;
        }
        return $this->s->first();
    }

    function next()
    {
        $x = $this->seq();
        if($x != null) return $x->next();
        return null;

        $this->seq();
        if ($this->s == null){
            return null;
        }
        return $this->s->next();
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