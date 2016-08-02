<?php

namespace phojure;


interface IPersistentCollection extends Seqable, IEq
{
    function cons($a);
    function nothing();
}