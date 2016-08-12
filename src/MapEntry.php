<?php


namespace phojure;


class MapEntry extends APersistentVector implements IMapEntry
{
    private $key;
    private $val;

    /**
     * MapEntry constructor.
     * @param $key
     * @param $val
     */
    public function __construct($key, $val)
    {
        $this->key = $key;
        $this->val = $val;
    }


    static function create($key, $val){
        return new MapEntry($key, $val);
    }

    /**
     * @return mixed
     */
    public function key()
    {
        return $this->key;
    }

    /**
     * @return mixed
     */
    public function val()
    {
        return $this->val;
    }

    function nothing()
    {
        return null;
    }

    function pop()
    {
        return LazilyPersistentVector::createOwning([$this->key]);
    }

    function assocN($i, $val)
    {
        return $this->asVector()->assocN($i, $val);
    }

    private function asVector()
    {
        return LazilyPersistentVector::createOwning([$this->key, $this->val]);
    }

    function cons($val)
    {
        return $this->asVector()->cons($val);
    }

    function nth($i)
    {
        if($i == 0) return $this->key;
        if($i == 1) return $this->val;

        throw new \OutOfBoundsException();
    }

    public function count()
    {
        return 2;
    }
}