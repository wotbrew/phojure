<?php


namespace phojure;


interface ITransientMap extends ITransientAssociative, \Countable
{
    function assoc($key, $val);
    function without($key);
    function persistent();
}