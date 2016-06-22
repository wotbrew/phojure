<?php
/**
 * Created by PhpStorm.
 * User: danielstone
 * Date: 22/06/2016
 * Time: 14:35
 */

namespace phojure;


class IteratorSeq implements Seq
{
    private $iterator;

    /**
     * IteratorSeq constructor.
     * @param $iterator
     */
    public function __construct($iterator)
    {
        $this->iterator = $iterator;
    }

    function first()
    {
        // TODO: Implement first() method.
    }

    function rest()
    {
        // TODO: Implement rest() method.
    }

}