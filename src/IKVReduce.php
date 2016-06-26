<?php


namespace phojure;


interface IKVReduce
{
    function reduceKV($f, $init);
}