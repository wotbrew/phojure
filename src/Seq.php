<?php

namespace phojure;


interface Seq
{
    function first();
    function next();
    function rest();
    function cons($x);
}