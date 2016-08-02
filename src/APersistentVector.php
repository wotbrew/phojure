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

    function peek()
    {
        if($this->count() > 0)
            return $this->nth($this->count() - 1);
        return null;
    }

    function __invoke()
    {
        $args = func_get_args();
        if(count($args) == 1){
            return $this->nth($args[0]);
        }
        if(count($args) == 2){
            return $this->nthOr($args[0], $args[1]);
        }
        throw new \Exception("Arity error. Expected 1 or 2 args.");
    }

    public function offsetExists($offset)
    {
        return $offset >= 0 && $offset < $this->count();
    }

    public function offsetGet($offset)
    {
        return $this->nth($offset);
    }

    public function offsetSet($offset, $value)
    {
        throw new \Exception();
    }

    public function offsetUnset($offset)
    {
        throw new \Exception();
    }

    function eq($a)
    {
        return $this->seq()->eq($a);
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
            if($ret instanceof Reduced) return $ret->deref();
            $ret = $f($ret, $v->nth($i));
        }

        if($ret instanceof Reduced) return $ret->deref();
        return $ret;
    }

}