<?php


namespace phojure;


interface Associative extends ILookup
{
    function containsKey($key);
    function entryAt($key);
    function assoc($key, $val);
}