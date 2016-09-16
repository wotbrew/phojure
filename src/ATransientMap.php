<?php


namespace phojure;


abstract class ATransientMap implements ITransientMap
{
    abstract function ensureEditable();
    abstract function doAssoc($key, $val);
    abstract function doWithout($key);

    abstract function doValAt($key, $notFound);
    abstract function doCount();
    abstract function doPersistent();

    public function conj($val)
    {
        $this->ensureEditable();
        if($val instanceof MapEntry)
        {
            return $this->assoc($val->key(), $val->val());
        }
        elseif ($val instanceof IPersistentVector)
        {
            if($val->count() != 2){
                throw new \Exception("Vector arg to map conj must be a pair");
            }
            return $this->assoc($val->nth(0), $val->nth(1));
        }

        $ret = $this;
        for($es = Coll::seq($val); $es != null; $es = $es->next()){
            $e = $es->first();
            $ret = $ret->assoc($e->key(), $e->val());
        }
        return $ret;
    }

    function __invoke($key, $notFound = null)
    {
        return $this->valAtOr($key, $notFound);
    }

    function valAt($key)
    {
        return $this->valAtOr($key, null);
    }

    function assoc($key, $val)
    {
        $this->ensureEditable();
        return $this->doAssoc($key, $val);
    }
    
    function without($key)
    {
        $this->ensureEditable();
        return $this->doWithout($key);
    }
    
    function persistent()
    {
        $this->ensureEditable();
        return $this->doPersistent();
    }
    
    function valAtOr($key, $notFound)
    {
        $this->ensureEditable();
        return $this->doValAt($key, $notFound);
    }
    
    function count()
    {
        $this->ensureEditable();
        return $this->doCount();
    }

}