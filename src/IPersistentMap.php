<?php


namespace phojure;


interface IPersistentMap extends \Countable, \IteratorAggregate, Associative
{
    function assoc($key, $val);

    function assocEx($key, $val);

    function without($key);
}