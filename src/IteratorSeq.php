<?php
/**
 * Created by PhpStorm.
 * User: danielstone
 * Date: 22/06/2016
 * Time: 14:35
 */

namespace phojure;

use Traversable;

class _IteratorSeqState
{
    public $val;
    public $rest;
}

class IteratorSeq extends ASeq
{
    /**
     * @var \Iterator
     */
    private $iterator;
    private $state;

    /**
     * IteratorSeq constructor.
     * @param $iterator
     */
    public function __construct($iterator)
    {
        $this->iterator = $iterator;
        $this->state = new _IteratorSeqState();
        $this->state->val = $this->state;
        $this->state->rest = $this->state;
    }

    public static function create(\Iterator $iterator){
        if($iterator->valid()){
            return new IteratorSeq($iterator);
        }
        return null;
    }

    function first()
    {
        if($this->state === $this->state->val){
            $this->state->val = $this->iterator->current();
            $this->iterator->next();
        }
        return $this->state->val;
    }

    function next()
    {
        if($this->state === $this->state->rest){
            $this->first();
            $this->state->rest = self::create($this->iterator);
        }
        return $this->state->rest;
    }

    public function getIterator()
    {
        return new SeqIterator($this);
    }

    function nothing()
    {
        return EmptyList::get();
    }
}