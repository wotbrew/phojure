<?php
/**
 * Created by PhpStorm.
 * User: danielstone
 * Date: 22/06/2016
 * Time: 13:53
 */

namespace phojure;


interface Seqable
{
    /**
     * @return ISeq
     */
    function seq();
}