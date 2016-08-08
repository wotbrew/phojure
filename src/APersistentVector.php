<?php


namespace phojure;


use Traversable;

abstract class APersistentVector implements IPersistentVector, IHashEq, \IteratorAggregate
{
    private $hash = -1;

    function containsKey($key)
    {
        return $key >= 0 && $key < $this->count();
    }

    function entryAt($key)
    {
        if ($key >= 0 && $key < $this->count()) {
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
        if ($this->count() > 0) {
            return new APersistentVector_Seq($this, 0);
        }
        return null;
    }

    function nthOr($i, $notFound)
    {
        if ($i >= 0 && $i < $this->count())
            return $this->nth($i);

        return $notFound;
    }

    function peek()
    {
        if ($this->count() > 0)
            return $this->nth($this->count() - 1);
        return null;
    }

    function __invoke()
    {
        $args = func_get_args();
        if (count($args) == 1) {
            return $this->nth($args[0]);
        }
        if (count($args) == 2) {
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
        if ($a === $this) {
            return true;
        }

        if ($a instanceof IPersistentVector) {

            if ($a->count() !== $this->count()) {
                return false;
            }

            $cnt = $this->count();
            for ($i = 0; $i < $cnt; $i++) {
                if (!Val::eq($a->nth($i), $this->nth($i))) {
                    return false;
                }
            }
            return true;
        }

        if ($a instanceof Traversable) {

            if ($a instanceof \Countable) {
                if (count($a) !== $this->count()) {
                    return false;
                }
            }

            $i = 0;
            foreach ($a as $x) {
                $v = $this->nth($i);
                if (!Val::eq($x, $v)) {
                    return false;
                }
                $i++;
            }
            return true;
        }

        if ($a instanceof Sequential) {
            $seq = Coll::seq($a);
            $cnt = $this->count();
            for ($i = 0; $i < $cnt; $i++) {
                $fst = Coll::first($seq);
                $v = $this->nth($i);
                if ($seq === null || !Val::eq($v, $fst)) {
                    return false;
                }
                $seq = Coll::next($seq);
            }
            return $seq === null;
        }



        return false;
    }

    public function getIterator()
    {
        return new APersistentVector_Iterator($this);
    }

    function hash()
    {
        if ($this->hash === -1) {
            $n = 0;
            $hash = 1;
            for ($i = 0; $i < $this->count(); $i++) {
                $hash = 31 * $hash + Val::hash($this->nth($i));
                ++$n;
            }
            $this->hash = Murmur3::mixCollHash($hash, $n);
        }
        return $this->hash;
    }
}

class APersistentVector_Iterator implements \Iterator
{

    private $count;
    private $i;
    private $vec;

    /**
     * APersistentVector_Iterator constructor.
     */
    public function __construct(IPersistentVector $vec)
    {
        $this->vec = $vec;
        $this->i = 0;
        $this->count = $vec->count();
    }

    public function current()
    {
        return $this->vec->nth($i);
    }

    public function next()
    {
        $this->i++;
    }

    public function key()
    {
        return $this->i;
    }

    public function valid()
    {
        return $this->i < $this->count;
    }

    public function rewind()
    {
        return $this->i = 0;
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
        if ($this->i + 1 < $this->vec->count()) {
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
        for ($i = 0; $i < $count; $i++) {
            if ($ret instanceof Reduced) return $ret->deref();
            $ret = $f($ret, $v->nth($i));
        }

        if ($ret instanceof Reduced) return $ret->deref();
        return $ret;
    }

}