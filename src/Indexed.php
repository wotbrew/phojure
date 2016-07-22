<?php


namespace phojure;


interface Indexed extends \Countable, \ArrayAccess
{
    function nth($i);
    function nthOr($i, $notFound);
}