<?php


namespace phojure;


interface IPersistentVector extends Associative, IPersistentStack
{
    function assocN($i, $val);
}