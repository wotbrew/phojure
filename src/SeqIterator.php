<?php
/**
 * Created by PhpStorm.
 * User: danielstone
 * Date: 22/06/2016
 * Time: 13:56
 */

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
        Core::first($this->s);
    }

    public function next()
    {
        $this->i++;
        $this->s = Core::rest($this->s);
    }

    public function key()
    {
        return $this->i;
    }

    public function valid()
    {
        return Core::seq($this->s) != null;
    }

    public function rewind()
    {
        $this->s = $this->so;
        $this->i = 0;
    }
}