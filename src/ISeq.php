<?php

namespace phojure;


interface ISeq
{
    function first();
    function next();
    function rest();
    function cons($x);
}