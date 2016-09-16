<?php


namespace phojure;


interface ITransientAssociative extends ITransientCollection, ILookup
{
    function containsKey($key);
    function assoc($key, $val);
}