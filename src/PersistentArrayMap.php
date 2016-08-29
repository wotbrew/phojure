<?php


namespace phojure;

class ArrayMapSeq extends ASeq implements \Countable
{
    private $array;
    private $i;

    public function __construct($arr, $i)
    {
        $this->array = $arr;
        $this->i = $i;
    }

    function count()
    {
        return count($this->array) / 2;
    }


    function first()
    {
        return MapEntry::create($this->array[$this->i], $this->array[$this->i+1]);
    }

    function next()
    {
        if($this->i + 2 < count($this->array)){
            return new ArrayMapSeq($this->array, $this->i + 2);
        }

        return null;
    }
}

class PersistentArrayMap extends APersistentMap implements IEditableCollection, IKVReduce, IMapIterable
{

    private $array;
    const HASHTABLE_THRESHOLD = 16;

    /**
     * PersistentArrayMap constructor.
     * @param $array
     */
    function __construct($array = [])
    {
        $this->array = $array;
    }

    static function getEmpty()
    {
        static $empty;
        if (!$empty) {
            $empty = new PersistentArrayMap();
        }
        return $empty;
    }

    static function ofEntryTraversable($map)
    {
        $ret = self::getEmpty()->asTransient();
        foreach ($map as $e) {
            $ret = $ret->assoc($e->key(), $e->val());
        }
        return $ret->persistent();
    }

    static function ofSeqArray($arr)
    {
        return new PersistentArrayMap($arr);
    }

    static function equalKey($k1, $k2)
    {
        if ($k1 instanceof Keyword) {
            return $k1 === $k2;
        }

        return Val::eq($k1, $k2);
    }

    static function createWithCheck($arr)
    {
        $len = count($arr);
        for ($i = 0; $i < $len; $i += 2) {
            for ($j = $i + 2; $j < $len; $j += 2) {
                if (self::equalKey($arr[$i], $arr[$j]))
                    throw new \Exception("Duplicate key: " . $arr[$i]);
            }
        }
        return new PersistentArrayMap($arr);
    }

    static function createAsIfByAssoc($arr)
    {
        $len = count($arr);

        if (($len & 1) === 1) {
            $s = $arr[$len - 1];
            throw new \Exception("No value supplied for key: $s");
        }

        $n = 0;
        for ($i = 0; $i < $len; $i += 2) {
            $duplicateKey = false;
            for ($j = 0; $j < $i; $j += 2) {
                if (self::equalKey($arr[$i], $arr[$j])) {
                    $duplicateKey = true;
                    break;
                }
            }
            if (!$duplicateKey)
                $n += 2;
        }
        if ($n < $len) {
            $nodups = array_fill(0, $n, null);;
            $m = 0;
            for ($i = 0; $i < $len; $i += 2) {
                $duplicateKey = false;
                for ($j = 0; $j < $m; $j += 2) {
                    if (self::equalKey($arr[$i], $nodups[$j])) {
                        $duplicateKey = true;
                        break;
                    }
                }
                if (!$duplicateKey) {
                    for ($j = $len - 2; $j >= $i; $j -= 2) {
                        if (self::equalKey($arr[$i], $arr[$j])) {
                            break;
                        }
                    }
                    $nodups[$m] = $arr[$i];
                    $nodups[$m + 1] = $arr[$j + 1];
                    $m += 2;
                }
            }
            if ($m != $n)
                throw new \Exception("Internal error: m=" . $m);
            $arr = $nodups;
        }
        return new PersistentArrayMap($arr);
    }

    static function create(... $kvs)
    {
        return self::createWithCheck($kvs);
    }

    private function indexOf($key)
    {
        $len = count($this->array);

        if ($key instanceof Keyword) {
            for ($i = 0; $i < $len; $i += 2) {
                if ($key === $this->array[$i])
                    return $i;
            }
        } else {
            for ($i = 0; $i < $len; $i += 2) {
                if (self::equalKey($key, $this->array[$i]))
                    return $i;
            }
        }
        return -1;
    }

    function containsKey($key)
    {
        return $this->indexOf($key) >= 0;
    }

    function entryAt($key)
    {
        $i = $this->indexOf($key);
        if ($i >= 0)
            return MapEntry::create($this->array[$i], $this->array[$i + 1]);
        return null;
    }

    function getIterator()
    {
        $len = count($this->array);
        for($i = 0; $i < $len; $i+=2) {
            yield MapEntry::create($this->array[$i], $this->array[$i+1]);
        }
    }

    function keyIterator()
    {
        $len = count($this->array);
        for($i = 0; $i < $len; $i+=2) {
            yield $this->array[$i];
        }
    }

    function valIterator()
    {
        $len = count($this->array);
        for($i = 0; $i < $len; $i+=2) {
            yield $this->array[$i+1];
        }
    }

    function asTransient()
    {
        return new TransientArrayMap($this->array);
    }

    function reduceKV($f, $init)
    {
        $len = count($this->array);
        for ($i = 0; $i < $len; $i += 2) {
            $init = call_user_func($f, $init, $this->array[$i], $this->array[$i + 1]);
            if ($init instanceof Reduced)
                return $init->deref();
        }
        return $init;
    }

    function valAt($key)
    {
        return $this->valAtOr($key, null);
    }

    function valAtOr($key, $notFound)
    {
        $i = $this->indexOf($key);
        if ($i >= 0) {
            return $this->array[$i + 1];
        }
        return $notFound;
    }

    function nothing()
    {
        return self::getEmpty();
    }

    static function createHT($array)
    {
        return PersistentHashMap::ofSequentialArray($array);
    }


    function assoc($key, $val)
    {
        $i = $this->indexOf($key);
        $newArray = $this->array;
        if ($i >= 0) //already have key, same-sized replacement
        {
            if ($this->array[$i + 1] === $val) //no change, no op
                return $this;

            $newArray[$i + 1] = $val;
        } else //didn't have key, grow
        {
            $len = count($this->array);
            if ($len > self::HASHTABLE_THRESHOLD)
                return self::createHT($this->array)->assoc($key, $val);

            array_push($newArray, $key);
            array_push($newArray, $val);
        }
        return self::ofSeqArray($newArray);
    }

    function without($key)
    {
        $i = $this->indexOf($key);
        if ($i >= 0) //have key, will remove
        {
            $newlen = count($this->array) - 2;
            if ($newlen == 0)
                return $this->getEmpty();
            $newArray = $this->array;
            $newArray = array_slice($newArray, 0, $i);
            $newArray = array_merge($newArray, $i + 2, $newlen - $i);
            return self::ofSeqArray($newArray);
        }
        //don't have key, no op
        return $this;
    }

    function count()
    {
        return intval(count($this->array) / 2);
    }

    function seq()
    {
        return new ArrayMapSeq($this->array, 0);
    }

}

class TransientArrayMap extends ATransientMap
{
    private $len;
    private $array;
    private $owner;

    public function __construct($array)
    {
        $newArray = $array;
        $this->array = $newArray;
        $this->len = count($array);
        $this->owner = new EmptyBox();
    }

    function ensureEditable()
    {
        if($this->owner === null){
            throw new \Exception("Transient used after persistent call");
        }
    }

    function equalKey($key1, $key2)
    {
        if($key1 instanceof Keyword)
            return $key1 === $key2;

        return Val::eq($key1, $key2);
    }

    function indexOf($key)
    {
        for($i = 0; $i < $this->len; $i+=2){
            if($this->equalKey($key, $this->array[$i])){
                return $i;
            }
        }
        return -1;
    }

    function doAssoc($key, $val)
    {
        $i = $this->indexOf($key);
        if($i >= 0){
            if($this->array[$i + 1] !== $val){
                $this->array[$i + 1] = $val;
            }
        }
        else {
            if($this->len >= count($this->array)){
                return PersistentHashMap::ofSequentialArray($this->array)->asTransient()->assoc($key, $val);
            }
        }
        return $this;
    }

    function doWithout($key)
    {
        $i = $this->indexOf($key);
        if($i >= 0){
            if($this->len >= 2){
                $this->array[$i] = $this->array[$this->len - 2];
                $this->array[$i+1] = $this->array[$this->len - 1];
            }
            $this->len -= 2;
        }
        return $this;
    }

    function doValAt($key, $notFound)
    {
        $i = $this->indexOf($key);
        if($i >= 0){
            return $this->array[$i+1];
        }
        return $notFound;
    }

    function doCount()
    {
        return $this->len / 2;
    }

    function doPersistent()
    {
        $this->ensureEditable();
        $this->owner = null;
        $newArray = $this->array;
        return new PersistentArrayMap($newArray);
    }
}