<?php


namespace phojure;


interface Associative extends ILookup, IPersistentCollection
{
    function containsKey($key);
    function entryAt($key);
    function assoc($key, $val);
}