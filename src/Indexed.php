<?php


namespace phojure;


interface Indexed extends \Countable
{
    function nth($i);
    function nthOr($i, $notFound);
}