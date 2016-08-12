<?php


namespace phojure;


class MapEntry
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


}