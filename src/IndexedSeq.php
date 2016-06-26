<?php


namespace phojure;


interface IndexedSeq extends ISeq, Sequential, \Countable
{
    function index();
}