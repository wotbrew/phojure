<?php


namespace phojure;


interface IPersistentVector extends Associative, IPersistentStack, Indexed, Sequential
{
    function assocN($i, $val);
    function cons($val);
}