<?php


namespace phojure;


interface ITransientVector extends ITransientAssociative, Indexed
{
    function assocN($i, $val);
    function pop();
}