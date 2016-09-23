<?php


namespace phojure;


interface ITransientMap extends ITransientAssociative, \Countable
{
    function assoc($key, $val);
    function dissoc($key);
    function persistent();
}