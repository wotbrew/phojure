<?php


namespace phojure;


use Traversable;

abstract class APersistentMap implements IPersistentMap, IHashEq
{

    private $hash = -1;

    function hash()
    {
        if ($this->hash === -1) {
            $this->hash = Murmur3::hashUnordered($this);
        }
        return $this->hash;
    }

    function eq($o)
    {
        if (!$o instanceof IPersistentMap)
            return false;

        if ($o->count() != $this->count())
            return false;

        for ($s = $this->seq(); $s != null; $s = $s->next()) {
            /**
             * @var MapEntry $e
             */
            $e = $s->first();
            $found = $o->containsKey($e->key());

            if (!$found || !Val::eq($e->val(), $o->valAt($e->key())))
                return false;
        }

        return true;
    }

    function cons($o)
    {
        if ($o instanceof MapEntry) {
            return $this->assoc($o->key(), $o->val());
        } elseif ($o instanceof IPersistentVector) {
            if (count($o) != 2)
                throw new \Exception("Vector arg to map conj must be a pair");
            return $this->assoc($o->nth(0), $o->nth(1));
        }

        $ret = $this;
        for ($es = Coll::seq($o); $es != null; $es = $es->next()) {
            /**
             * @var MapEntry $e
             */
            $e = $es->first();
            $ret = $ret->assoc($e->key(), $e->val());
        }
        return $ret;
    }

    function __invoke($key, $notFound = null)
    {
        return $this->valAtOr($key, $notFound);
    }


}