<?php


namespace phojure;


interface ILookup
{
    function valAt($key);
    function valAtOr($key, $notFound);
}