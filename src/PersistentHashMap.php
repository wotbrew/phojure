<?php


namespace phojure;

interface IPersistentHashMapNode
{
    function assoc($shift, $hash, $key, $val, $addedLeaf);

    function assocT($edit, $shift, $hash, $key, $val, $addedLeaf);

    function without($shift, $hash, $key);

    function withoutT($edit, $shift, $hash, $key, $removedLeaf);

    function findEntry($shift, $hash, $key);

    function find($shift, $hash, $key, $notFound);

    function nodeSeq();

    function reduceKV($f, $init);

    function iterator($f);
}

class PersistentHashMapNodeSeq extends ASeq
{
    private $array;
    private $i;
    private $s;

    public function __construct($array, $i, $s)
    {
        $this->i = $i;
        $this->array = $array;
        $this->s = $s;
    }

    static function create($array)
    {
        return self::create2($array, 0, null);
    }

    static function create2($array, $i, $s)
    {
        if($s !== null){
            return new PersistentHashMapNodeSeq($array, $i, $s);
        }
        $len = count($array);
        for($j=$i;$j<$len;$j+=2){
            if($array[$j] !== null){
                return new PersistentHashMapNodeSeq($array, $j, null);
            }

            $node = $array[$j+1];
            if($node !== null){
                $nodeSeq = $node->nodeSeq();
                if($nodeSeq !== null){
                    return new PersistentHashMapNodeSeq($array, $j+2, $nodeSeq);
                }
            }
        }
        return null;
    }

    function first()
    {
        if ($this->s !== null) {
            return $this->s->first();
        }
        return MapEntry::create($this->array[$this->i], $this->array[$this->i + 1]);
    }

    function next()
    {
        if($this->s !== null){
            return self::create2($this->array, $this->i+2, $this->s->next());
        }

        return null;
    }


}


class PersistentHashMapArrayNodeSeq extends ASeq
{
    private $nodes;
    private $i;
    private $s;

    /**
     * PersistentHashMapArrayNodeSeq constructor.
     * @param $nodes
     * @param $i
     * @param $s
     */
    public function __construct($nodes, $i, $s)
    {
        $this->nodes = $nodes;
        $this->i = $i;
        $this->s = $s;
    }

    static function _create($nodes, $i, $seq)
    {
        if ($seq !== null)
            return new PersistentHashMapArrayNodeSeq($nodes, $i, $seq);
        $len = count($nodes);
        for ($j = $i; $j < $len; $j++) {
            if ($nodes[$j] !== null) {
                $ns = $nodes[$j]->nodeSeq();
                if ($ns !== null) {
                    return new PersistentHashMapArrayNodeSeq($nodes, $j + 1, $ns);
                }
            }
        }
        return null;
    }

    static function create($nodes)
    {
        return self::_create($nodes, 0, null);
    }

    function first()
    {
        return $this->s->first();
    }

    function next()
    {
        return self::_create($this->nodes, $this->i, $this->s->next());
    }

    static function reduceKV($array, $f, $init)
    {
        $len = count($array);
        for ($i = 0; $i < $len; $i += 2) {
            if ($array[$i] !== null) {
                $init = call_user_func($f, $init, $array[$i], $array[$i + 1]);
            } else {
                /**
                 * @var IPersistentHashMapNode $node
                 */
                $node = $array[$i + 1];
                if ($node != null)
                    $init = $node->reduceKV($f, $init);
            }
            if ($init instanceof Reduced)
                return $init->deref();
        }
        return $init;
    }
}

class PersistentHashMapArrayNode implements IPersistentHashMapNode
{
    private $count;
    private $array;
    private $edit;

    public function __construct($edit, $count, &$array)
    {
        $this->count = $count;
        $this->array = $array;
        $this->edit = $edit;
    }

    function assoc($shift, $hash, $key, $val, $addedLeaf)
    {
        $idx = PersistentHashMap::mask($hash, $shift);
        $node = $this->array[$idx];
        if ($node === null) {
            $newArray = $this->array;
            $newArray[$idx] = PersistentHashMapBitmapNode::getEmpty()
                ->assoc($shift + 5, $hash, $key, $val, $addedLeaf);
            return new PersistentHashMapArrayNode(null, $this->count + 1, $newArray);
        }
        $n = $node->assoc($shift + 5, $hash, $key, $val, $addedLeaf);
        if ($n === $node)
            return $this;

        $newArray = $this->array;
        $newArray[$idx] = $n;
        return new PersistentHashMapArrayNode(null, $this->count, $newArray);
    }

    function ensureEditable($edit)
    {
        if ($this->edit === $edit)
            return $edit;

        $newArray = $this->array;
        return new PersistentHashMapArrayNode($edit, $this->count, $newArray);
    }

    function pack($edit, $idx)
    {
        $newArray = array_fill(0, 2 * ($this->count - 1), null);
        $j = 1;
        $bitmap = 0;
        for ($i = 0; $i < $idx; $i++) {
            if ($this->array[$i] !== null) {
                $newArray[$j] = $this->array[$i];
                $bitmap |= 1 << $i;
                $j += 2;
            }
        }
        $len = count($this->array);
        for ($i = $idx + 1; $i < $len; $i++) {
            if ($this->array[$i] !== null) {
                $newArray[$j] = $this->array[$i];
                $bitmap |= 1 << $i;
                $j += 2;
            }
        }
        return new PersistentHashMapBitmapNode($edit, $bitmap, $newArray);
    }

    function without($shift, $hash, $key)
    {
        $idx = PersistentHashMap::mask($hash, $shift);
        $node = $this->array[$idx];
        if ($node === null) {
            return $this;
        }
        $n = $node->without($shift + 5, $hash, $key);
        if ($n === $node) {
            return $this;
        }

        if ($n === null) {
            if ($this->count <= 8) {
                return $this->pack(null, $idx);
            }

            $newArray = $this->array;
            $newArray[$idx] = $n;
            return new PersistentHashMapArrayNode(null, $this->count - 1, $newArray);
        }

        $newArray = $this->array;
        $newArray[$idx] = $n;
        return new PersistentHashMapArrayNode(null, $this->count, $newArray);

    }

    function findEntry($shift, $hash, $key)
    {
        $idx = PersistentHashMap::mask($hash, $shift);
        $node = $this->array[$idx];

        if ($node === null)
            return null;

        return $node->findEntry($shift + 5, $hash, $key);
    }

    function find($shift, $hash, $key, $notFound)
    {
        $idx = PersistentHashMap::mask($hash, $shift);
        $node = $this->array[$idx];

        if ($node === null)
            return $notFound;

        return $node->find($shift + 5, $hash, $key, $notFound);
    }

    function nodeSeq()
    {
        return PersistentHashMapArrayNodeSeq::create($this->array);
    }

    function iterator($f)
    {
        $i = 0;
        $len = count($this->array);
        $nestedIter = null;
        while (true) {
            if ($nestedIter !== null) {
                foreach ($nestedIter as $x) {
                    yield $x;
                }
                $nestedIter = null;
            }
            if ($i < $len) {
                $node = $this->array[$i++];
                if ($node !== null) {
                    $nestedIter = $node->iterator($f);
                }
            } else {
                break;
            }
        }
    }

    function reduceKV($f, $init)
    {
        foreach ($this->array as $node) {
            if ($node !== null) {
                $init = $node->reduceKV($f, $init);
                if ($init instanceof Reduced) {
                    return $init;
                }
            }
        }
        return $init;
    }

    function editAndSet($edit, $i, $node)
    {
        $editable = $this->ensureEditable($edit);
        $editable->array[$i] = $node;
        return $editable;
    }

    function assocT($edit, $shift, $hash, $key, $val, $addedLeaf)
    {
        $idx = PersistentHashMap::mask($hash, $shift);
        $node = $this->array[$idx];
        if ($node === null) {
            $newNode = PersistentHashMapBitmapNode::getEmpty()->assocT($edit, $shift + 5, $hash, $key, $val, $addedLeaf);
            $editable = $this->editAndSet($edit, $idx, $newNode);
            $editable->count++;
            return $editable;
        }
        $n = $node->assocT($edit, $shift + 5, $hash, $key, $val, $addedLeaf);
        if ($n === $node)
            return $this;

        return $this->editAndSet($edit, $idx, $n);
    }

    function withoutT($edit, $shift, $hash, $key, $removedLeaf)
    {
        $idx = PersistentHashMap::mask($hash, $shift);
        $node = $this->array[$idx];
        if ($node === null)
            return $this;
        $n = $node->withoutT($edit, $shift + 5, $hash, $key, $removedLeaf);
        if ($n === $node) {
            return $this;
        }
        if ($n === null) {
            if ($this->count <= 8)
                return $this->pack($edit, $idx);
            $editable = $this->editAndSet($edit, $idx, $n);
            $editable->count--;
            return $editable;
        }
        return $this->editAndSet($edit, $idx, $n);
    }

}

class PersistentHashMapBitmapNode implements IPersistentHashMapNode
{
    private $bitmap;
    private $array;
    private $edit;

    /**
     * PersistentHashMapBitmapNode constructor.
     * @param $bitmap
     * @param $array
     * @param $edit
     */
    public function __construct($edit, $bitmap, &$array)
    {
        $this->bitmap = $bitmap;
        $this->array = $array;
        $this->edit = $edit;
    }

    static function getEmpty()
    {
        static $empty;
        if (!$empty) {
            $arr = [];
            $empty = new PersistentHashMapBitmapNode(null, 0, $arr);
        }
        return $empty;
    }

    function index($bit)
    {
        return Util::bitCount($this->bitmap & ($bit - 1));
    }

    function assoc($shift, $hash, $key, $val, $addedLeaf)
    {
        $bit = PersistentHashMap::bitpos($hash, $shift);
        $idx = $this->index($bit);

        if (($this->bitmap & $bit) !== 0) {
            $keyOrNull = $this->array[2 * $idx];
            $valOrNode = $this->array[2 * $idx + 1];
            if ($keyOrNull === null) {
                $n = $valOrNode->assoc($shift + 5, $hash, $key, $val, $addedLeaf);
                if ($n === $valOrNode)
                    return $this;
                $newArr = $this->array;
                $newArr[2 * $idx + 1] = $n;
                return new PersistentHashMapBitmapNode(null, $this->bitmap, $newArr);
            }
            if (Val::eq($key, $keyOrNull)) {
                if ($val === $valOrNode)
                    return $this;
                $newArr = $this->array;
                $newArr[2 * $idx + 1] = $val;
                return new PersistentHashMapBitmapNode(null, $this->bitmap, $newArr);
            }
            $addedLeaf->val = $val;
            $newArr = $this->array;
            $newArr[2 * $idx] = null;
            $newArr[2 * $idx + 1] = PersistentHashMap::createNode($shift + 5, $keyOrNull, $valOrNode, $hash, $key, $val);
            return new PersistentHashMapBitmapNode(null, $this->bitmap, $newArr);
        } else {
            $n = Util::bitCount($this->bitmap);
            if ($n >= 16) {
                $nodes = array_fill(0, 32, null);
                $jdx = PersistentHashMap::mask($hash, $shift);
                $nodes[$jdx] = self::getEmpty()->assoc($shift + 5, $hash, $key, $val, $addedLeaf);
                $j = 0;
                for ($i = 0; $i < 32; $i++) {
                    if ((Util::uRShift($this->bitmap, $i) & 1) != 0) {
                        if ($this->array[$j] === null) {
                            $nodes[$i] = $this->array[$j + 1];
                        } else {
                            $nodes[$i] = self::getEmpty()->assoc($shift + 5,
                                Val::hash($this->array[$j]),
                                $this->array[$j],
                                $this->array[$j + 1],
                                $addedLeaf);
                        }
                        $j += 2;
                    }
                }
                return new PersistentHashMapArrayNode(null, $n + 1, $nodes);
            } else {
                //could be faster
                $newArr = array_fill(0, 2 * ($n + 1), null);
                Util::arrayCopy($this->array, 0, $newArr, 0, 2 * $idx);
                $newArr[2 * $idx] = $key;
                $newArr[2 * $idx + 1] = $val;
                $addedLeaf->val = $addedLeaf;
                Util::arrayCopy($this->array, 2 * $idx, $newArr, 2 * ($idx + 1), 2 * ($n - $idx));
                return new PersistentHashMapBitmapNode(null, $this->bitmap | $bit, $newArr);
            }
        }
    }

    function without($shift, $hash, $key)
    {
        $bit = PersistentHashMap::bitpos($hash, $shift);
        if (($this->bitmap & $bit) === 0) {
            return $this;
        }
        $idx = $this->index($bit);
        $keyOrNull = $this->array[2 * $idx];
        $valOrNode = $this->array[2 * $idx + 1];
        if ($keyOrNull === null) {
            $n = $valOrNode->without($shift + 5, $hash, $key);
            if ($n === $valOrNode)
                return $this;
            if ($n !== null) {
                $newArr = $this->array;
                $newArr[2 * $idx + 1] = $n;
                return new PersistentHashMapBitmapNode(null, $this->bitmap, $newArr);
            }
            if ($this->bitmap === $bit) {
                return null;
            }
            $newArr = PersistentHashMap::removePair($this->array, $idx);
            return new PersistentHashMapBitmapNode(null, $this->bitmap ^ $bit, $newArr);
        }
        if (Val::eq($key, $keyOrNull)) {
            $newArr = PersistentHashMap::removePair($this->array, $idx);
            return new PersistentHashMapBitmapNode(null, $this->bitmap ^ $bit, $newArr);
        }
        return $this;
    }

    function find($shift, $hash, $key, $notFound)
    {
        $bit = PersistentHashMap::bitpos($hash, $shift);
        if (($this->bitmap & $bit) === 0) {
            return $notFound;
        }
        $idx = $this->index($bit);
        $keyOrNull = $this->array[2 * $idx];
        $valOrNode = $this->array[2 * $idx + 1];
        if ($keyOrNull === null)
            return $valOrNode->find($shift + 5, $hash, $key, $notFound);
        if (Val::eq($key, $keyOrNull))
            return $valOrNode;
        return $notFound;
    }

    function findEntry($shift, $hash, $key)
    {
        $bit = PersistentHashMap::bitpos($hash, $shift);
        if (($this->bitmap & $bit) === 0) {
            return null;
        }
        $idx = $this->index($bit);
        $keyOrNull = $this->array[2 * $idx];
        $valOrNode = $this->array[2 * $idx + 1];
        if ($keyOrNull === null)
            return $valOrNode->findEntry($shift + 5, $hash, $key);
        if (Val::eq($key, $keyOrNull))
            return MapEntry::create($keyOrNull, $valOrNode);
        return null;
    }

    function nodeSeq()
    {
        return PersistentHashMapNodeSeq::create($this->array);
    }

    function iterator($f)
    {
        $len = count($this->array);

        for ($i = 0; $i < $len; $i += 2) {
            $keyOrNull = $this->array[$i];
            $valOrNode = $this->array[$i + 1];

            if ($keyOrNull !== null) {
                yield call_user_func($f, $keyOrNull, $valOrNode);
            } elseif ($valOrNode !== null) {
                foreach ($valOrNode->iterator($f) as $entry) {
                    yield $entry;
                }
            }
        }
    }

    function reduceKV($f, $init)
    {
        return PersistentHashMapArrayNodeSeq::reduceKV($this->array, $f, $init);
    }

    function ensureEditable($edit)
    {
        if ($this->edit === $edit)
            return $this;

        $n = Util::bitCount($this->bitmap);
        $newArray = array_fill(0, $n >= 0 ? 2 * ($n + 1) : 4, null);
        Util::arrayCopy($this->array, 0, $newArray, 0, 2 * $n);
        return new PersistentHashMapBitmapNode($edit, $this->bitmap, $newArray);
    }

    function editAndSet($edit, $i, $a)
    {
        $editable = $this->ensureEditable($edit);
        $editable->array[$i] = $a;
        return $editable;
    }

    function editAndSet2($edit, $i, $a, $j, $b)
    {
        $editable = $this->ensureEditable($edit);
        $editable->array[$i] = $a;
        $editable->array[$j] = $b;
        return $editable;
    }

    function editAndRemovePair($edit, $bit, $i)
    {
        if ($this->bitmap === $bit) {
            return null;
        }
        $editable = $this->ensureEditable($edit);
        $editable->bitmap ^= $bit;
        $len = count($editable->array);
        Util::arrayCopy($editable->array, 2 * ($i + 1), $editable->array, 2 * $i, $len - 2 * ($i + 1));
        $editable->array[$len - 2] = null;
        $editable->array[$len - 1] = null;
        return $editable;
    }

    function assocT($edit, $shift, $hash, $key, $val, $addedLeaf)
    {
        $bit = PersistentHashMap::bitpos($hash, $shift);
        $idx = $this->index($bit);

        if (($this->bitmap & $bit) !== 0) {
            $keyOrNull = $this->array[2 * $idx];
            $valOrNode = $this->array[2 * $idx + 1];
            if ($keyOrNull === null) {
                $n = $valOrNode->assocT($edit, $shift + 5, $hash, $key, $val, $addedLeaf);
                if ($n === $valOrNode) {
                    return $this;
                }
                return $this->editAndSet($edit, 2 * $idx + 1, $val);
            }
            if (Val::eq($key, $keyOrNull)) {
                if ($val === $valOrNode) {
                    return $this;
                }
                return $this->editAndSet($edit, 2 * $idx + 1, $val);
            }
            $addedLeaf->val = $addedLeaf;
            $n = PersistentHashMap::createNodeT($edit, $shift + 5, $keyOrNull, $valOrNode, $hash, $key, $val);
            return $this->editAndSet2($edit, 2 * $idx, null, 2 * $idx + 1, $n);
        } else {
            $n = Util::bitCount($this->bitmap);
            if ($n * 2 < count($this->array)) {
                $addedLeaf->val = $addedLeaf;
                $editable = $this->ensureEditable($edit);
                Util::arrayCopy($editable->array, 2 * $idx, $editable->array, 2 * ($idx + 1), 2 * ($n - $idx));
                $editable->array[2 * $idx] = $key;
                $editable->array[2 * $idx + 1] = $val;
                $editable->bitmap |= $bit;
                return $editable;
            }

            if ($n >= 16) {
                $nodes = array_fill(0, 32, null);
                $jdx = PersistentHashMap::mask($hash, $shift);
                $nodes[$jdx] = self::getEmpty()->assocT($edit, $shift + 5, $hash, $key, $val, $addedLeaf);
                $j = 0;
                for ($i = 0; $i < 32; $i++) {
                    if ((Util::uRShift($this->bitmap, $i) & 1) !== 0) {
                        if ($this->array[$j] === null) {
                            $nodes[$i] = $this->array[$j + 1];
                        } else {
                            $nodes[$i] = self::getEmpty()->assocT($edit,
                                $shift + 5,
                                Val::hash($this->array[$j]),
                                $this->array[$j],
                                $this->array[$j + 1],
                                $addedLeaf);
                        }
                        $j += 2;
                    }
                }
                return new PersistentHashMapArrayNode($edit, $n + 1, $nodes);
            } else {
                $newArr = array_fill(0, 2 * ($n + 4), null);
                Util::arrayCopy($this->array, 0, $newArr, 0, 2 * $idx);
                $newArr[2 * $idx] = $key;
                $newArr[2 * $idx + 1] = $val;
                $addedLeaf->val = $addedLeaf;

                Util::arrayCopy($this->array, 2 * $idx, $newArr, 2 * ($idx + 1), 2 * ($n - $idx));
                $editable = $this->ensureEditable($edit);
                $editable->array = $newArr;
                $editable->bitmap |= $bit;
                return $editable;
            }
        }
    }

    function withoutT($edit, $shift, $hash, $key, $removedLeaf)
    {
        $bit = PersistentHashMap::bitpos($hash, $shift);
        if (($this->bitmap & $bit) === 0) {
            return $this;
        }

        $idx = $this->index($bit);
        $keyOrNull = $this->array[2 * $idx];
        $valOrNode = $this->array[2 * $idx + 1];

        if ($keyOrNull === null) {
            $n = $valOrNode->withoutT($edit, $shift + 5, $hash, $key, $removedLeaf);
            if ($n === $valOrNode) {
                return $this;
            }
            if ($n !== null) {
                return $this->editAndSet($edit, 2 * idx + 1, $n);
            }
            if ($this->bitmap === $bit) {
                return null;
            }
            return $this->editAndRemovePair($edit, $bit, $idx);
        } elseif (Val::eq($key, $keyOrNull)) {
            $removedLeaf->val = $removedLeaf;
            return $this->editAndRemovePair($edit, $bit, $idx);
        } else {
            return $this;
        }
    }
}

class PersistentHashMapCollisionNode implements IPersistentHashMapNode
{
    private $hash;
    private $count;
    private $array;
    private $edit;

    /**
     * PersistentHashMapCollisionNode constructor.
     * @param $hash
     * @param $count
     * @param $array
     * @param $edit
     */
    public function __construct($edit, $hash, $count, &$array)
    {
        $this->hash = $hash;
        $this->count = $count;
        $this->array = $array;
        $this->edit = $edit;
    }

    private function findIndex($key)
    {
        for ($i = 0; $i < 2 * $this->count; $i += 2) {
            if (Val::eq($key, $this->array[$i])) {
                return $i;
            }
        }
        return -1;
    }

    public function assoc($shift, $hash, $key, $val, $addedLeaf)
    {
        if ($hash === $this->hash) {
            $idx = $this->findIndex($key);
            if ($idx !== -1) {
                if ($this->array[$idx + 1] === $val) {
                    return $this;
                }
                $newArr = $this->array;
                $newArr[$idx + 1] = $val;
                return new PersistentHashMapCollisionNode(null, $hash, $this->count, $newArr);
            }
            $newArray = array_fill(0, 2 * ($this->count + 1), null);
            Util::arrayCopy($this->array, 0, $newArray, 0, 2 * $this->count);
            $newArr[2 * $this->count] = $key;
            $newArr[2 * $this->count + 1] = $val;

            $addedLeaf->val = $addedLeaf;
            return new PersistentHashMapCollisionNode($this->edit, $hash, $this->count + 1, $newArray);
        }

        $narr = [null, $this];
        $node = new PersistentHashMapBitmapNode(null, PersistentHashMap::bitpos($this->hash, $shift), $narr);
        return $node->assoc($shift, $hash, $key, $val, $addedLeaf);
    }

    public function without($shift, $hash, $key)
    {
        $idx = $this->findIndex($key);
        if ($idx === -1) {
            return $this;
        }
        if ($this->count === 1) {
            return null;
        }
        return new PersistentHashMapCollisionNode(
            null,
            $hash,
            $this->count - 1,
            PersistentHashMap::removePair($this->array, $idx / 2));
    }

    function find($shift, $hash, $key, $notFound)
    {
        $idx = $this->findIndex($key);
        if ($idx === -1) {
            return $notFound;
        }

        return $this->array[$idx + 1];
    }

    function findEntry($shift, $hash, $key)
    {
        $idx = $this->findIndex($key);
        if ($idx === -1) {
            return null;
        }

        return MapEntry::create($this->array[$idx], $this->array[$idx + 1]);
    }

    function nodeSeq()
    {
        return PersistentHashMapNodeSeq::create($this->array);
    }

    function iterator($f)
    {
        $len = count($this->array);

        for ($i = 0; $i < $len; $i += 2) {
            $keyOrNull = $this->array[$i];
            $valOrNode = $this->array[$i + 1];

            if ($keyOrNull !== null) {
                yield call_user_func($f, $keyOrNull, $valOrNode);
            } elseif ($valOrNode !== null) {
                foreach ($valOrNode as $entry) {
                    yield $entry;
                }
            }
        }
    }

    function reduceKV($f, $init)
    {
        return PersistentHashMapArrayNodeSeq::reduceKV($this->array, $f, $init);
    }

    private function ensureEditable($edit)
    {
        if ($this->edit === $edit) {
            return $this;
        }

        $newArray = $this->array;
        return new PersistentHashMapCollisionNode($edit, $this->hash, $this->count, $newArray);
    }

    private function ensureEditable2($edit, $count, &$array)
    {
        if ($this->edit == $edit) {
            $this->array = $array;
            $this->count = $count;
        }

        return new PersistentHashMapCollisionNode($edit, $this->hash, $count, $array);
    }

    private function editAndSet($edit, $i, $a)
    {
        $editable = $this->ensureEditable($edit);
        $editable->array[$i] = $a;
        return $editable;
    }

    private function editAndSet2($edit, $i, $a, $j, $b)
    {
        $editable = $this->ensureEditable($edit);
        $editable->array[$i] = $a;
        $editable->array[$j] = $b;
        return $editable;
    }

    function assocT($edit, $shift, $hash, $key, $val, $addedLeaf)
    {
        if ($hash === $this->hash) {
            $idx = $this->findIndex($key);
            if ($idx !== -1) {
                if ($this->array[$idx + 1] === $val) {
                    return $this;
                }
                return $this->editAndSet($edit, $idx, $val);
            } elseif (count($this->array) > 2 * $this->count) {
                $addedLeaf->val = $addedLeaf;
                $newArrayNode = $this->editAndSet2($edit, 2 * $this->count, $key, 2 * $this->count + 1, $val);
                $newArrayNode->count++;
                return $newArrayNode;
            } else {
                $len = count($this->array);
                $newArray = array_fill(0, $len + 2, null);
                Util::arrayCopy($this->array, 0, $newArray, 0, $len);
                $newArray[$len] = $key;
                $newArray[$len + 1] = $val;
                $addedLeaf->val = $addedLeaf;
                return $this->ensureEditable($edit, $this->count + 1, $newArray);
            }
        }
        $narr = [null, $this, null, null];
        $node = new PersistentHashMapBitmapNode($edit, PersistentHashMap::bitpos($hash, $shift), $narr);

        return $node->assocT($edit, $shift, $hash, $key, $val, $addedLeaf);
    }

    function withoutT($edit, $shift, $hash, $key, $removedLeaf)
    {
        $idx = $this->findIndex($key);
        if ($idx === -1) {
            return $this;
        }
        $removedLeaf->val = $removedLeaf;
        if ($this->count === 1) {
            return null;
        }
        $editable = $this->ensureEditable($edit);
        $editable->array[$idx] = $editable->array[2 * $this->count - 2];
        $editable->array[$idx + 1] = $editable->array[2 * $this->count - 1];
        $editable->array[2 * $this->count - 2] = null;
        $editable->array[2 * $this->count - 1] = null;
        $editable->count--;

        return $editable;
    }
}

class PersistentHashMap extends APersistentMap implements IEditableCollection, IKVReduce, IMapIterable
{
    private $count;
    /**
     * @var IPersistentHashMapNode
     */
    private $root;
    private $hasNull;
    private $nullValue;
    private $notFound;

    static function create(... $items)
    {
        return self::ofSequentialArray($items);
    }

    static function mask($hash, $shift)
    {
        return Util::uRShift($hash, $shift) & 31;
    }

    static function bitpos($hash, $shift)
    {
        return (1 << self::mask($hash, $shift));
    }

    static function removePair(array $arr, $i)
    {
        $newArr = array_fill(0, count($arr) - 2, null);
        Util::arrayCopy($arr, 0, $newArr, 0, 2 * $i);
        Util::arrayCopy($arr, 2 * ($i + 1), $newArr, 2 * $i, count($newArr) - 2 * $i);
        return $newArr;
    }

    static function createNode($shift, $key1, $val1, $key2hash, $key2, $val2)
    {
        $key1Hash = Val::hash($key1);
        if ($key1Hash === $key2hash) {
            $arr = [$key1, $val1, $key2, $val2];
            return new PersistentHashMapCollisionNode(null, $key1Hash, 2, $arr);
        }
        $addedLeaf = new Box(null);
        $edit = new EmptyBox();
        return PersistentHashMapBitmapNode::getEmpty()
            ->assocT($edit, $shift, $key1Hash, $key1, $val1, $addedLeaf)
            ->assocT($edit, $shift, $key2hash, $key2, $val2, $addedLeaf);
    }

    static function createNodeT($edit, $shift, $key1, $val1, $key2hash, $key2, $val2)
    {
        $key1Hash = Val::hash($key1);
        if ($key1Hash === $key2hash) {
            $arr = [$key1, $val1, $key2, $val2];
            return new PersistentHashMapCollisionNode(null, $key1Hash, 2, $arr);
        }
        $addedLeaf = new Box(null);
        return PersistentHashMapBitmapNode::getEmpty()
            ->assocT($edit, $shift, $key1Hash, $key1, $val1, $addedLeaf)
            ->assocT($edit, $shift, $key2hash, $key2, $val2, $addedLeaf);
    }

    /**
     * PersistentHashMap constructor.
     * @param $count
     * @param $root
     * @param $hasNull
     * @param $nullValue
     */
    public function __construct($count, $root, $hasNull, $nullValue)
    {
        $this->count = $count;
        $this->root = $root;
        $this->hasNull = $hasNull;
        $this->nullValue = $nullValue;
        $this->notFound = new EmptyBox();
    }

    static function ofSequentialArray($items)
    {
        $ret = self::getEmpty()->asTransient();
        $len = count($items);
        for ($i = 0; $i < $len; $i += 2) {
            $ret = $ret->assoc($items[$i], $items[$i + 1]);
        }
        return $ret->persistent();
    }

    static function ofSequentialArrayWithCheck($items)
    {
        $ret = self::getEmpty()->asTransient();
        $len = count($items);
        for ($i = 0; $i < $len; $i += 2) {
            $ret = $ret->assoc($items[$i], $items[$i + 1]);
            if ($ret->count() !== $i / 2 + 1) {
                throw new \Exception("Duplicate key ${items[$i]}");
            }
        }
        return $ret->persistent();
    }

    static function ofSeq($items)
    {
        $ret = self::getEmpty()->asTransient();
        for (; $items !== null; $items = $items->next()->next()) {
            if ($items->next() == null) {
                $fst = $items->first();
                throw new \Exception("No value supplied for key ${fst}");
            }
            $ret = $ret->assoc($items->first(), Coll::second($items));
        }

        return $ret->persistent();
    }

    static function ofSeqWithCheck($items)
    {
        $ret = self::getEmpty()->asTransient();
        for ($i = 0; $items !== null; $items = $items->next()->next(), ++$i) {
            if ($items->next() == null) {
                $fst = $items->first();
                throw new \Exception("No value supplied for key ${fst}");
            }
            $ret = $ret->assoc($items->first(), Coll::second($items));
            if ($ret->count() != $i + 1) {
                throw new \Exception("Duplicate key: " . $items->first());
            }
        }

        return $ret->persistent();
    }

    static function ofEntryTraversable($map)
    {
        $ret = self::getEmpty()->asTransient();
        foreach ($map as $e) {
            $ret = $ret->assoc($e->key(), $e->val());
        }
        return $ret->persistent();
    }

    static function getEmpty()
    {
        static $empty;
        if (!$empty) {
            $empty = new PersistentHashMap(0, null, false, null);
        }
        return $empty;
    }

    function containsKey($key)
    {
        if ($key === null) {
            return $this->hasNull;
        }
        if ($this->root !== null) {
            return $this->root->find(0, Val::hash($key), $key, $this->notFound) !== $this->notFound;
        }
        return false;
    }

    function entryAt($key)
    {
        if ($key === null) {
            if ($this->hasNull) {
                return MapEntry::create(null, $this->nullValue);
            }
            return null;
        }
        if ($this->root !== null) {
            return $this->root->findEntry(0, Val::hash($key), $key);
        }
        return null;
    }

    private function iterator($f)
    {
        $rootIter = $this->root === null ? new \ArrayIterator([]) : $this->root->iterator($f);
        if ($this->hasNull) {
            yield call_user_func($f, null, $this->nullValue);
        }
        foreach ($rootIter as $x) {
            yield $x;
        }
    }

    public function getIterator()
    {
        return $this->iterator(function ($key, $val) {
            return MapEntry::create($key, $val);
        });
    }

    public function keyIterator()
    {
        return $this->iterator(function ($key, $val) {
            return $key;
        });
    }

    public function valIterator()
    {
        return $this->iterator(function ($key, $val) {
            return $val;
        });
    }

    function asTransient()
    {
        return new TransientHashMap(new Box(42), $this->root, $this->count, $this->hasNull, $this->nullValue);
    }

    function reduceKV($f, $init)
    {
        if ($this->hasNull) {
            $init = call_user_func($f, null, $this->nullValue);
        }
        if ($init instanceof Reduced) {
            return $init->deref();
        } elseif ($this->root !== null) {
            $init = $this->root->reduceKV($f, $init);
            return $init instanceof Reduced ? $init->deref() : $init;
        } else {
            return $init;
        }
    }

    function valAt($key)
    {
        return $this->valAtOr($key, null);
    }

    function valAtOr($key, $notFound)
    {
        if ($key === null) {
            if ($this->hasNull) {
                return $this->nullValue;
            }
            return $notFound;
        }

        if ($this->root !== null) {
            return $this->root->find(0, Val::hash($key), $key, $notFound);
        }

        return $notFound;
    }

    function nothing()
    {
        return self::getEmpty();
    }

    function assoc($key, $val)
    {
        if ($key === null) {
            if ($this->hasNull) {
                if ($this->nullValue === $val) {
                    return $this;
                }
                return new PersistentHashMap($this->count, $this->root, true, $val);
            }
            return new PersistentHashMap($this->count + 1, $this->root, true, $val);
        }
        $addedLeaf = new Box(null);
        $root = $this->root ?: PersistentHashMapBitmapNode::getEmpty();
        $newRoot = $root->assoc(0, Val::hash($key), $key, $val, $addedLeaf);
        if ($newRoot === $root) {
            return $this;
        }
        $newCount = $addedLeaf->val !== null ? $this->count + 1 : $this->count;
        return new PersistentHashMap($newCount, $newRoot, $this->hasNull, $this->nullValue);
    }

    function without($key)
    {
        if ($key === null) {
            if ($this->hasNull) {
                return new PersistentHashMap($this->count - 1, $this->root, false, null);
            }
            return $this;
        }
        if ($this->root === null) {
            return $this;
        }
        $newRoot = $this->root->without(0, Val::hash($key), $key);
        if ($newRoot === $this->root) {
            return $this;
        }
        return new PersistentHashMap($this->count - 1, $newRoot, $this->hasNull, $this->nullValue);
    }


    public function count()
    {
        return $this->count;
    }

    function seq()
    {
        $s = $this->root !== null ? $this->root->nodeSeq() : null;
        if ($this->hasNull) {
            return new Cons(MapEntry::create(null, $this->nullValue), $s);
        }
        return $s;
    }


}

class TransientHashMap extends ATransientMap
{
    private $edit;
    /**
     * @var IPersistentHashMapNode
     */
    private $root;
    private $count;
    private $hasNull;
    private $nullValue;

    private $leafFlag;

    /**
     * TransientHashMap constructor.
     */
    public function __construct($edit, $node, $count, $hasNull, $nullValue)
    {
        $this->leafFlag = new Box(null);
        $this->edit = $edit;
        $this->root = $node;
        $this->count = $count;
        $this->hasNull = $hasNull;
        $this->nullValue = $nullValue;
    }

    function ensureEditable()
    {
        if($this->edit->val === null){
            throw new \Exception('Transient used after persistent call');
        }
    }

    function doAssoc($key, $val)
    {
        if($key === null){
            if($this->nullValue !== $val){
                $this->nullValue = $val;
            }
            if(!$this->hasNull){
                $this->count++;
                $this->hasNull = true;
            }
            return $this;
        }
        else {
            $this->leafFlag->val = null;
            $n = $this->root ?: PersistentHashMapBitmapNode::getEmpty();
            $n = $n->assocT($this->edit, 0, Val::hash($key), $key, $val, $this->leafFlag);
            if($n !== $this->root){
                $this->root = $n;
            }
            if($this->leafFlag->val !== null){
                $this->count++;
            }
            return $this;
        }
    }

    function doWithout($key)
    {
        if($key === null){
            if(!$this->hasNull){
                return $this;
            }
            else {
                $this->hasNull = false;
                $this->nullValue = null;
                $this->count--;
                return $this;
            }
        }
        elseif ($this->root === null){
            return $this;
        }
        else {
            $this->leafFlag->val = null;
            $n = $this->root->withoutT($this->edit, 0, Val::hash($key), $key, $this->leafFlag);
            if($n !== $this->root){
                $this->root = $n;
            }
            if($this->leafFlag->val !== null){
                $this->count--;
            }
            return $this;
        }
    }

    function doValAt($key, $notFound)
    {
        if($key === null){
            if($this->hasNull){
                return $this->nullValue;
            }
            return $notFound;
        }
        if($this->root !== null){
            return $this->root->find(0, Val::hash($key), $key, $notFound);
        }
        return $notFound;
    }

    function doCount()
    {
        return $this->count;
    }

    function doPersistent()
    {
        $this->edit->val = null;
        return new PersistentHashMap($this->count, $this->root, $this->hasNull, $this->nullValue);
    }
}