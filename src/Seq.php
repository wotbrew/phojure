<?php
/**
 * Created by PhpStorm.
 * User: danielstone
 * Date: 22/06/2016
 * Time: 13:37
 */

namespace phojure;


interface Seq
{
    function first();
    function rest();
}