<?php


namespace phojure;


interface ITransientCollection
{
    function conj($val);
    function persistent();
}