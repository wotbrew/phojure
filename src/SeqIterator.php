<?php


namespace phojure;


class SeqIterator implements \Iterator
{
    private $s;
    private $so;
    private $i = 0;

    /**
     * SeqIterator constructor.
     * @param $s
     */
    public function __construct($s)
    {
        $this->s = $s;
        $this->so = $s;
    }

    public function current()
    {
        return Coll::first($this->s);
    }

    public function next()
    {
        $this->i++;
        $this->s = Coll::rest($this->s);
    }

    public function key()
    {
        return $this->i;
    }

    public function valid()
    {
        return Coll::seq($this->s) !== null;
    }

    public function rewind()
    {
        $this->s = $this->so;
        $this->i = 0;
    }
}