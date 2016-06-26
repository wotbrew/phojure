<?php


namespace phojure;


interface ITransientAssociative extends ITransientCollection, ILookup
{
    function assoc($key, $val);
}