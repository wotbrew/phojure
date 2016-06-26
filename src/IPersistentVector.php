<?php


namespace phojure;


interface IPersistentVector extends Associative, IPersistentStack, Indexed
{
    function assocN($i, $val);
}