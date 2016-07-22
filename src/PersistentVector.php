<?php


namespace phojure;

class PersistentVector_Node
{
    public $array;
    public $edit;

    /**
     * PersistentVector_Node constructor.
     * @param $array
     * @param $edit
     */
    public function __construct($edit, $array)
    {
        $this->array = $array;
        $this->edit = $edit;
    }

}

class PersistentVector extends APersistentVector implements IEditableCollection, IReduce, IKVReduce
{
    private static $NO_EDIT = null;


    private static function emptyNode()
    {
        static $node;
        if (!$node) {
            $arr = new \SplFixedArray(32);
            $node = new PersistentVector_Node(self::$NO_EDIT, $arr);
        }
        return $node;
    }

    static function getEmpty()
    {
        static $empty;
        if (!$empty) {
            $empty = new PersistentVector(0, 5, self::emptyNode(), new \SplFixedArray());
        }
        return $empty;
    }

    private $count;
    private $shift;
    /**
     * @var PersistentVector_Node
     */
    private $root;
    /**
     * @var \SplFixedArray
     */
    private $tail;

    /**
     * PersistentVector constructor.
     * @param $count
     * @param $shift
     * @param $root
     * @param $tail
     */
    public function __construct($count, $shift, $root, $tail)
    {
        $this->count = $count;
        $this->shift = $shift;
        $this->root = $root;
        $this->tail = $tail;
    }

    private function tailOff()
    {
        if ($this->count < 32)
            return 0;

        return Util::uRShift($this->count() - 1, 5) << 5;
    }

    private function arrayFor($i)
    {
        if ($i >= 0 && $i < $this->count) {
            if ($i >= $this->tailOff()) {
                return $this->tail;
            }
            $node = $this->root;

            for ($level = $this->shift; $level > 0; $level -= 5) {
                $node = $node->array[(Util::uRShift($i, $level)) & 0x01f];
            }
            return $node->array;
        }
        throw new \OutOfBoundsException();
    }

    public function nth($i)
    {
        $arr = $this->arrayFor($i);
        return $arr[$i & 0x01f];
    }


    function asTransient()
    {
        return PersistentVector_Transient::ofPersistentVector($this);
    }

    function reduce($f, $init)
    {
        $step = 0;
        for($i = 0; $i < $this->count; $i+=$step){
            $arr = $this->arrayFor($i);
            for($j = 0; $j < $arr->count(); ++$j){
                $init = call_user_func($f, $init, $arr[$j]);
                if($init instanceof Reduced){
                    return $init->deref();
                }
                $step = $arr->count();
            }
        }
        return $init;
    }

    function reduceKV($f, $init)
    {
        $step = 0;
        for($i = 0; $i < $this->count; $i+=$step){
            $arr = $this->arrayFor($i);
            for($j = 0; $j < $arr->count(); ++$j){
                $init = call_user_func($f, $init, $j+$i, $arr[$j]);
                if($init instanceof Reduced){
                    return $init->deref();
                }
                $step = $arr->count();
            }
        }
        return $init;
    }

    function nothing()
    {
        return self::getEmpty();
    }

    private function popTail($level, PersistentVector_Node $node){
        $subidx = Util::uRShift($this->count - 2, 0x01f);
        if($level > 5){
          $newchild = $this->popTail($level - 5, $node->array[$subidx]);
          if($newchild == null && $subidx == 0){
              return null;
          }
          else {
              $ret = new PersistentVector_Node($this->root->edit, clone($node->array));
              $ret->array[$subidx] = $newchild;
              return $ret;
          }
        }
        else if ($subidx == 0){
            return null;
        }
        else {
            $ret = new PersistentVector_Node($this->root->edit, clone($this->root->array));
            $ret->array[$subidx] = null;
            return $ret;
        }
    }

    function pop()
    {
        $cnt = $this->count;
        if($cnt == 0) throw new \Exception("Can't pop empty vector");
        if($cnt == 1) return $this->nothing();
        
        if($cnt - $this->tailOff()){
            $newTail = new \SplFixedArray($this->tail->count() - 1);
            Util::splArrayCopy($this->tail, 0, $newTail, 0, $newTail->count());
            return new PersistentVector($cnt-1, $this->shift, $this->root, $newTail);
        }

        $newTail = $this->arrayFor($cnt - 2);
        $newRoot = $this->popTail($this->shift, $this->root);
        $newShift = $this->shift;
        if($newRoot == null){
            $newRoot = self::emptyNode();
        }
        if($this->shift > 5 && $newRoot->array[1] == null){
            $newRoot = $newRoot->array[0];
            $newShift -= 5;
        }
        return new PersistentVector($cnt -1, $newShift, $newRoot, $newTail);
    }

    function assocN($i, $val)
    {
        if($i >= 0 && $i < $this->count){
            if($i > $this->tailOff()){
                $newtail = new \SplFixedArray($this->tail->count());
                Util::splArrayCopy($this->tail, 0, $newtail, 0, $this->tail->count());
                $newtail[$i & 0x01f];

                return new PersistentVector($this->count, $this->shift, $this->root, $newtail);
            }
            return new PersistentVector($this->count, $this->shift,
                $this->doAssoc($this->shift, $this->root, $i, $val),
                $this->tail);
        }
        if($i == $this->count){
            return $this->cons($val);
        }
        throw new \OutOfBoundsException();
    }

    private function doAssoc($level, $node, $i, $val){
        $ret = new PersistentVector_Node($node->edit, clone $node->array);
        if($level == 0){
            $ret->array[$i & 0x01f] = $val;
        }
        else {
            $subidx = Util::uRShift($i, $level) & 0x01f;
            $ret->array[$subidx] = $this->doAssoc($level - 5, $ret->array[$subidx], $i, $val);
        }
        return $ret;
    }


    public function count()
    {
        return $this->count;
    }

    static function ofSeq(ISeq $coll)
    {
        $arr = new \SplFixedArray(32);
        $i = 0;
        for (; $coll != null && $i < 32; $coll = $coll->next()) {
            $arr[$i++] = $coll->first();
        }
        if ($coll != null) {
            $start = new PersistentVector(32, 5, self::emptyNode(), $arr);
            $ret = $start->asTransient();
            for (; $coll != null; $coll = $coll->next()) {
                $ret = $ret->conj($coll->first());
            }
            return $ret->persistent();
        } else if ($i == 32) {
            return new PersistentVector(32, 5, self::emptyNode(), $arr);
        } else {
            $arr2 = new \SplFixedArray($i);
            Util::splArrayCopy($arr, 0, $arr2, 0, $i);
            return new PersistentVector($i, 5, self::emptyNode(), $arr2);
        }
    }

    static $ofColl = 'phojure\\PersistentVector::ofColl';
    static function ofColl($coll)
    {
        if ($coll == null) return self::getEmpty();
        return self::ofSeq(Coll::seq($coll));
    }

    function UNSAFE_getTail()
    {
        return $this->tail;
    }

    function UNSAFE_getRoot()
    {
        return $this->root;
    }

    function UNSAFE_getShift()
    {
        return $this->shift;
    }

    function cons($val)
    {
        $count = $this->count;
        $tail = $this->tail;
        $shift = $this->shift;
        $root = $this->root;

        if ($count - $this->tailOff() < 32) {
            $newTail = new \SplFixedArray($tail->count() + 1);
            Util::splArrayCopy($tail, 0, $newTail, 0, $tail->count());
            $newTail[$tail->count()] = $val;
            return new PersistentVector($count + 1, $shift, $root, $newTail);
        }
        $newroot = null;
        $tailnode = new PersistentVector_Node($root->edit, $tail);
        $newshift = $shift;
        if (Util::uRShift($count, 5) > (1 << $shift)) {
            $newroot = new PersistentVector_Node($root->edit, new \SplFixedArray(32));
            $newroot->array[0] = $root;
            $newroot->array[1] = $this->newPath($root->edit, $shift, $tailnode);
            $newshift += 5;
        } else {
            $newroot = $this->pushTail($shift, $root, $tail);
        }

        $tail2 = new \SplFixedArray(1);
        $tail2[0] = $val;
        return new PersistentVector($count + 1, $newshift, $newroot, $tail2);
    }

    private function pushTail($level, $parent, $tailnode)
    {
        $subidx = Util::uRShift($this->count - 1, $level) & 0x01f;
        $ret = new PersistentVector_Node($parent->edit, clone $parent->array);
        $nodeToInsert = null;
        if ($level == 5) {
            $nodeToInsert = $tailnode;
        } else {
            $child = $parent->array[$subidx];
            $nodeToInsert = $child != null ?
                $this->pushTail($level - 5, $child, $tailnode) :
                $this->newPath($this->root->edit, $level - 5, $tailnode);
        }
        $ret->array[$subidx] = $nodeToInsert;
        return $ret;
    }

    private function newPath($edit, $level, $node)
    {
        if ($level == 0) return $node;
        $ret = new PersistentVector_Node($edit, new \SplFixedArray(32));
        $ret->array[0] = $this->newPath($edit, $level - 5, $node);
        return $ret;
    }
}


class PersistentVector_Transient implements ITransientVector
{
    private $count;
    private $shift;
    private $root;
    private $tail;

    private function __construct($count, $shift, $root, $tail)
    {
        $this->tail = $tail;
        $this->root = $root;
        $this->shift = $shift;
        $this->count = $count;
    }

    static function ofPersistentVector(PersistentVector $vec)
    {
        return new PersistentVector_Transient($vec->count(),
            $vec->UNSAFE_getShift(),
            self::editableRoot($vec->UNSAFE_getRoot()),
            self::editableTail($vec->UNSAFE_getTail()));
    }

    static function editableRoot($root)
    {
        static $ctr = 0;
        $ctr++;
        return new PersistentVector_Node($ctr, clone $root->array);
    }

    static function editableTail(\SplFixedArray $tail)
    {
        $ret = new \SplFixedArray(32);
        Util::splArrayCopy($tail, 0, $ret, 0, $tail->count());
        return $ret;
    }

    private function ensureEditable()
    {
        if ($this->root->edit == null) {
            var_dump($this->root);
            throw new \Exception("Transient used after persistent call");
        }
    }

    private function ensureEditableNode($node)
    {
        if ($this->root->edit === $node->edit)
            return $node;

        return new PersistentVector_Node($this->root->edit, clone $node->array);
    }

    function tailOff()
    {
        if ($this->count < 32)
            return 0;
        return Util::uRShift($this->count - 1, 5) << 5;
    }

    function valAt($key)
    {
        return $this->nth($key);
    }

    function valAtOr($key, $notFound)
    {
        return $this->nthOr($key, $notFound);
    }

    function assoc($key, $val)
    {

    }

    function conj($val)
    {
        $this->ensureEditable();
        $root = $this->root;
        $tail = $this->tail;
        $i = $this->count;

        if ($i - $this->tailOff() < 32) {
            $tail[$i & 0x01f] = $val;
            $this->count++;
            return $this;
        }

        $newroot = null;
        $tailnode = new PersistentVector_Node($root->edit, $tail);
        $this->tail = new \SplFixedArray(32);
        $tail = $this->tail;
        $tail[0] = $val;
        $newshift = $this->shift;
        if (Util::uRShift($this->count, 5) > (1 << $this->shift)) {
            $newroot = new PersistentVector_Node($root->edit, new \SplFixedArray(32));
            $newroot->array[0] = $root;
            $newroot->array[1] = $this->newPath($root->edit, $this->shift, $tailnode);
            $newshift += 5;
        } else {
            $newroot = $this->pushTail($this->shift, $root, $tailnode);
        }

        $this->root = $newroot;
        $this->shift = $newshift;
        $this->count++;
        return $this;
    }

    private function pushTail($level, $parent, $tailnode)
    {

        //if parent is leaf, insert node,
        // else does it map to an existing child? -> nodeToInsert = pushNode one more level
        // else alloc new path
        //return  nodeToInsert placed in parent
        $count = $this->count;
        $parent = $this->ensureEditableNode($parent);
        $subidx = Util::uRShift($count - 1, $level) & 0x01f;
        $ret = $parent;
        $nodeToInsert = null;
        if ($level == 5) {
            $nodeToInsert = $tailnode;
        } else {
            $child = $parent->array[$subidx];
            $nodeToInsert = ($child != null) ?
                $this->pushTail($level - 5, $child, $tailnode)
                : $this->newPath($this->root->edit, $level - 5, $tailnode);
        }
        $ret->array[$subidx] = $nodeToInsert;
        return $ret;
    }

    function persistent()
    {
        $this->ensureEditable();

        $trimmedTail = new \SplFixedArray($this->count - $this->tailOff());
        Util::splArrayCopy($this->tail, 0, $trimmedTail, 0, $trimmedTail->count());
        $this->edit = false;
        return new PersistentVector($this->count, $this->shift, $this->root, $trimmedTail);
    }

    private function newPath($edit, $level, $node)
    {
        if ($level == 0)
            return $node;
        $ret = new PersistentVector_Node($edit, new \SplFixedArray(32));
        $ret->array[0] = $this->newPath($edit, $level - 5, $node);
        return $ret;
    }

    function assocN($i, $val)
    {
        // TODO: Implement assocN() method.
    }

    function pop()
    {
        // TODO: Implement pop() method.
    }

    function nth($i)
    {

    }

    function nthOr($i, $notFound)
    {
        if($this->count >= $i && $i < $this->count){
            return $this->nth($i);
        }
        return $notFound;
    }

    public function count()
    {
        return $this->count;
    }
}