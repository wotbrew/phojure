<?php


namespace phojure;


abstract class APersistentVector implements IPersistentVector
{

    function containsKey($key)
    {
        return $key >= 0 && $key < $this->count();
    }

    function entryAt($key)
    {
        if($key >= 0 && $key < $this->count()){
            return MapEntry::create($key, $this->nth($key));
        }
        return null;
    }

    function assoc($key, $val)
    {
        return $this->assocN($key, $val);
    }

    function valAt($key)
    {
        return $this->nthOr($key, null);
    }

    function valAtOr($key, $notFound)
    {
        return $this->nthOr($key, $notFound);
    }

    function seq()
    {
        if($this->count() > 0){
            return new APersistentVector_Seq($this, 0);
        }
        return null;
    }

    function nthOr($i, $notFound)
    {
        if($i >= 0 && $i < $this->count())
            return $this->nth($i);

        return $notFound;
    }
}

class APersistentVector_Seq extends ASeq implements IndexedSeq, IReduce
{
    /**
     * @var IPersistentVector
     */
    private $vec;
    private $i;

    public function __construct($vec, $i)
    {
        $this->vec = $vec;
        $this->i = $i;
    }

    function first()
    {
        return $this->vec->nth($this->i);
    }

    function next()
    {
        if($this->i + 1 < $this->vec->count()){
            return new APersistentVector_Seq($this->vec, $this->i + 1);
        }
        return null;
    }

    function index()
    {
        return $this->i;
    }

    function count()
    {
        return $this->vec->count() - $this->i;
    }

    function reduce($f, $init)
    {
        $ret = $init;
        $v = $this->vec;
        $count = $v->count();
        for($i = 0; $i < $count; $i++){
            if(Core::is_reduced($ret)) return $ret->deref();
            $ret = $f($ret, $v->nth($i));
        }

        if(Core::is_reduced($ret)) return $ret->deref();
        return $ret;
    }
}