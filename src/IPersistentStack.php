<?php


namespace phojure;


interface IPersistentStack extends IPersistentCollection
{
    function peek();
    function pop();
}