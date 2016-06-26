<?php


namespace phojure;


class PersistentVector extends APersistentVector implements IEditableCollection, IReduce, IKVReduce
{
    static function getEmpty(){
        static $empty;
        if(!$empty){
            $empty = new PersistentVector(0, 5, new \SplFixedArray(32), new \SplFixedArray());
        }
        return $empty;
    }

    private $count;
    private $shift;
    /**
     * @var \SplFixedArray
     */
    private $root;
    /**
     * @var \SplFixedArray
     */
    private $tail;

    private static function emptyArray(){
        static $arr;
        if(!$arr) {
            $arr = new \SplFixedArray(32);
        }
        return $arr;
    }

    /**
     * PersistentVector constructor.
     * @param $count
     * @param $shift
     * @param $root
     * @param $tail
     */
    private function __construct($count, $shift, $root, $tail)
    {
        $this->count = $count;
        $this->shift = $shift;
        $this->root = $root;
        $this->tail = $tail;
    }

    private function tailOff()
    {
        if($this->count < 32)
            return 0;

        return Util::uRShift($this->count() - 1, 5) << 5;
    }

    private function arrayFor($i)
    {
        if($i >= 0 && $i < $this->count){
            if($i >= $this->tailOff()) {
                return $this->tail;
            }
            $node = $this->root;

            for($level = $this->shift; $level > 0; $level -= 5){
                $node = $node[(Util::uRShift($i, $level)) & 0x01f];
            }
            return $node;
        }
        throw new \OutOfBoundsException();
    }

    public function nth($i){

        $node = $this->arrayFor($i);
        return $node[$i & 0x01f];
    }


    function asTransient()
    {

    }

    function reduceKV($f, $init)
    {
        // TODO: Implement reduceKV() method.
    }

    function nothing()
    {
        return self::getEmpty();
    }

    function peek()
    {
        // TODO: Implement peek() method.
    }

    function pop()
    {
        // TODO: Implement pop() method.
    }

    function assocN($i, $val)
    {
        // TODO: Implement assocN() method.
    }

    function reduce($f, $init)
    {
        // TODO: Implement reduce() method.
    }

    public function count()
    {
        return $this->count;
    }


    static function ofSeq(ISeq $coll){
        $arr = new \SplFixedArray(32);
        $i = 0;
        for(; $coll != null && $i < 32; $coll = $coll->next()){
            $arr[$i++] = $coll->first();
        }
        if($coll != null) {
            $start = new PersistentVector(32, 5, self::emptyArray(), $arr);

        }
    }

    function UNSAFE_getTail()
    {
        return $this->tail;
    }
    function UNSAFE_getRoot()
    {
        return $this->root;
    }
    function UNSAFE_getShift()
    {
        return $this->shift;
    }

    function cons($val)
    {
        $count = $this->count;
        $tail = $this->tail;
        $shift = $this->shift;
        $root = $this->root;

        if($count - $this->tailOff() < 32){
            $newTail = new \SplFixedArray($tail->count() + 1);
            Util::splArrayCopy($tail, 0, $newTail, 0, $tail->count());
            $newTail[$tail->count()] = $val;
            return new PersistentVector($count + 1, $shift, $root, $newTail);
        }
        $newroot = null;
        $tailnode = $tail;
        $newshift = $shift;
        if(Util::uRShift($count, 5) > (1 << $shift)){
            $newroot = new \SplFixedArray(32);
            $newroot[0] = $root;
            $newroot[1] = $this->newPath($shift, $tailnode);
            $newshift+=5;
        }
        else
        {
            $newroot = $this->pushTail($shift, $root, $tail);
        }

        $tail2 = new \SplFixedArray(1);
        $tail2[0] = $val;
        return new PersistentVector($count + 1, $newshift, $newroot, $tail2);
    }

    private function pushTail($level, $parent, $tail)
    {
        $subidx = Util::uRShift($this->count - 1, $level) & 0x01f;
        $ret = clone $parent;
        $nodeToInsert = null;
        if($level == 5){
            $nodeToInsert = $tail;
        }
        else {
            $child = $parent[$subidx];
            $nodeToInsert = $child != null ?
                $this->pushTail($level-5, $child, $tail) :
                $this->newPath($level-5, $tail);
        }
        $ret[$subidx] = $nodeToInsert;
        return $ret;
    }

    private function newPath($level, $node){

        if($level == 0) return $node;
        $ret = new \SplFixedArray(32);
        $ret[0] = $this->newPath($level - 5, $node);
	    return $ret;
    }
}

class PersistentVector_Transient implements ITransientVector
{
    private $count;
    private $shift;
    private $root;
    private $tail;

    private function __construct($count, $shift, $root, $tail)
    {
        $this->tail = $tail;
        $this->root = $root;
        $this->shift = $shift;
        $this->count = $count;
    }

    static function ofPersistentVector(PersistentVector $vec){
        return new PersistentVector_Transient($vec->count(),
            $vec->UNSAFE_getTail(),
            self::editableRoot($vec->UNSAFE_getRoot()),
            self::editableTail($vec->UNSAFE_getTail()));
    }

    static function editableRoot(\SplFixedArray $root)
    {
        return clone $root;
    }

    static function editableTail(\SplFixedArray $tail)
    {
        return clone $tail;
    }

    function valAt($key)
    {
        // TODO: Implement valAt() method.
    }

    function valAtOr($key, $notFound)
    {
        // TODO: Implement valAtOr() method.
    }

    function assoc($key, $val)
    {
        // TODO: Implement assoc() method.
    }

    function conj($val)
    {
        // TODO: Implement conj() method.
    }

    function persistent()
    {
        // TODO: Implement persistent() method.
    }

    function assocN($i, $val)
    {
        // TODO: Implement assocN() method.
    }

    function pop()
    {
        // TODO: Implement pop() method.
    }

    function nth($i)
    {
        // TODO: Implement nth() method.
    }

    function nthOr($i, $notFound)
    {
        // TODO: Implement nthOr() method.
    }

    public function count()
    {
        // TODO: Implement count() method.
    }
}