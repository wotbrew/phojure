<?php

namespace phojure;

class EmptyList implements Seqable, ISeq, IPersistentCollection, \Iterator, \Countable, IPersistentStack {
    public static function get(){
        static $x;
        if(!$x) {
            $x = new EmptyList();
        }
        return $x;
    }

    function seq(){
        return null;
    }

    function first()
    {
        return null;
    }

    function next()
    {
        return null;
    }

    function rest()
    {
        return $this;
    }

    function cons($x)
    {
        return new PersistentList($x, null);
    }

    function nothing()
    {
        return $this;
    }

    public function current()
    {
        return null;
    }

    public function key()
    {
        return null;
    }

    public function valid()
    {
        return false;
    }

    public function rewind()
    {
    }

    function count()
    {
        return 0;
    }

    function peek()
    {
        return null;
    }

    function pop()
    {
        return null;
    }

    function eq($a)
    {
        return $a === self::get();
    }
}

class PersistentList extends ASeq implements IPersistentStack
{
    private $head;
    private $tail;

    function __construct($head, $tail)
    {
        $this->head = $head;
        $this->tail = $tail;
    }

    function cons($x)
    {
        return new PersistentList($x, $this);
    }

    function nothing()
    {
        return EmptyList::get();
    }

    function first()
    {
        return $this->head;
    }

    function next()
    {
        return $this->tail;
    }

    static function ofArray(array $array)
    {
        $list = EmptyList::get();
        $count = count($array);
        for($i = $count - 1; $i >= 0; $i--){
            $list = $list->cons($array[$i]);
        }
        return $list;
    }

    static function ofFixedArray(\SplFixedArray $array)
    {
        $list = EmptyList::get();
        $count = $array->count();
        for($i = $count - 1; $i >= 0; $i--){
            $list = $list->cons($array->offsetGet($i));
        }
        return $list;
    }

    function peek()
    {
        return $this->head;
    }

    function pop()
    {
        return $this->tail;
    }
}