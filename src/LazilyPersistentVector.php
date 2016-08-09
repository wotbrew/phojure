<?php


namespace phojure;


class LazilyPersistentVector
{
    public static function createOwning(array $items){
        $len = count($items);
        if($len === 0){
            return PersistentVector::getEmpty();
        }
        if($len <= 32){
            return new PersistentVector($len, 5, PersistentVector::emptyNode(), $items);
        }

        return PersistentVector::ofArray($items);
    }

    public static function create($coll){
        if($coll === null) {
            return PersistentVector::getEmpty();
        }
        if(is_array($coll)){
            return self::createOwning($coll);
        }
        if($coll instanceof ISeq){
            return PersistentVector::ofSeq($coll);
        }
        if($coll instanceof IReduce){
            return PersistentVector::ofReducable($coll);
        }
        if($coll instanceof \Traversable){
            return PersistentVector::ofTraversable($coll);
        }

        throw new \Exception('Could not create vector of ' . get_class($coll));
    }

}