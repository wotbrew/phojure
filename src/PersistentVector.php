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
    public function __construct($edit, &$array)
    {
        $this->array = $array;
        $this->edit = $edit;
    }

}

class PersistentVector extends APersistentVector implements IEditableCollection, IReduce, IKVReduce
{
    private static $NO_EDIT = null;


    public static function emptyNode()
    {
        static $node;
        if (!$node) {
            $arr = array_fill(0, 32, null);
            $node = new PersistentVector_Node(self::$NO_EDIT, $arr);
        }
        return $node;
    }

    static function getEmpty()
    {
        static $empty;
        if (!$empty) {
            $arr = [];
            $empty = new PersistentVector(0, 5, self::emptyNode(), $arr);
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
     * @var array
     */
    private $tail;

    /**
     * PersistentVector constructor.
     * @param $count
     * @param $shift
     * @param $root
     * @param $tail
     */
    public function __construct($count, $shift, $root, &$tail)
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
            for($j = 0; $j < count($arr); ++$j){
                $init = call_user_func($f, $init, $arr[$j]);
                if($init instanceof Reduced){
                    return $init->deref();
                }
                $step = count($arr);
            }
        }
        return $init;
    }

    function reduceKV($f, $init)
    {
        $step = 0;
        for($i = 0; $i < $this->count; $i+=$step){
            $arr = $this->arrayFor($i);
            for($j = 0; $j < count($arr); ++$j){
                $init = call_user_func($f, $init, $j+$i, $arr[$j]);
                if($init instanceof Reduced){
                    return $init->deref();
                }
                $step = count($arr);
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
              $newArr = $node->array;
              $ret = new PersistentVector_Node($this->root->edit, $newArr);
              $ret->array[$subidx] = $newchild;
              return $ret;
          }
        }
        else if ($subidx == 0){
            return null;
        }
        else {
            $newArr = $this->root->array;
            $ret = new PersistentVector_Node($this->root->edit, $newArr);
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
            $newTail = $this->tail;
            array_pop($newTail);
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

                $newtail = $this->tail;
                $newtail[$i & 0x01f] = $val;

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
        $newArr = $node->array;
        $ret = new PersistentVector_Node($node->edit, $newArr);
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
        $arr = array_fill(0, 32, null);
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
            $arr2 = array_slice($arr, 0, $i);
            return new PersistentVector($i, 5, self::emptyNode(), $arr2);
        }
    }

    static $ofColl = 'phojure\\PersistentVector::ofColl';
    static function ofColl($coll)
    {
        if ($coll == null) return self::getEmpty();
        return self::ofSeq(Coll::seq($coll));
    }

    static function ofReducable(IReduce $coll)
    {
        $ret = self::getEmpty()->asTransient();
        $ret = $coll->reduce(
            function(ITransientVector $vec, $x) {
                return $vec->conj($x);
            }, $ret);
        return $ret->persistent();
    }

    static function ofTraversable(\Traversable $coll)
    {
        $ret = self::getEmpty()->asTransient();
        foreach($coll as $x){
            $ret = $ret->conj($x);
        }
        return $ret->persistent();
    }

    static function ofArray(array $arr)
    {
        $ret = self::getEmpty()->asTransient();
        foreach($arr as $x){
            $ret = $ret->conj($x);
        }
        return $ret->persistent();
    }

    function &UNSAFE_getTail()
    {
        return $this->tail;
    }

    function &UNSAFE_getRoot()
    {
        return $this->root;
    }

    function &UNSAFE_getShift()
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
            $newTail = $tail;
            array_push($newTail, $val);
            return new PersistentVector($count + 1, $shift, $root, $newTail);
        }
        $newroot = null;
        $tailnode = new PersistentVector_Node($root->edit, $tail);
        $newshift = $shift;
        if (Util::uRShift($count, 5) > (1 << $shift)) {
            $newarr = array_fill(0,32,null);
            $newarr[0] = $root;
            $newarr[1] = $this->newPath($root->edit, $shift, $tailnode);
            $newroot = new PersistentVector_Node($root->edit, $newarr);
            $newshift += 5;
        } else {
            $newroot = $this->pushTail($shift, $root, $tail);
        }

        $tail2 = [$val];
        return new PersistentVector($count + 1, $newshift, $newroot, $tail2);
    }

    private function pushTail($level, $parent, $tailnode)
    {
        $subidx = Util::uRShift($this->count - 1, $level) & 0x01f;
        $newarr = $parent->array;
        $nodeToInsert = null;
        if ($level == 5) {
            $nodeToInsert = $tailnode;
        } else {
            $child = $parent->array[$subidx];
            $nodeToInsert = $child != null ?
                $this->pushTail($level - 5, $child, $tailnode) :
                $this->newPath($this->root->edit, $level - 5, $tailnode);
        }
        $newarr[$subidx] = $nodeToInsert;
        $ret = new PersistentVector_Node($parent->edit, $newarr);
        return $ret;
    }

    private function newPath($edit, $level, $node)
    {
        if ($level == 0) return $node;
        $newarr = array_fill(0,32,null);
        $newarr[0] = $this->newPath($edit, $level - 5, $node);
        $ret = new PersistentVector_Node($edit, $newarr);
        return $ret;
    }

}


class PersistentVector_Transient implements ITransientVector
{
    private $count;
    private $shift;
    private $root;
    private $tail;

    private function __construct($count, $shift, $root, &$tail)
    {
        $this->tail = $tail;
        $this->root = $root;
        $this->shift = $shift;
        $this->count = $count;
    }

    static function ofPersistentVector(PersistentVector $vec)
    {
        $tail = self::editableTail($vec->UNSAFE_getTail());

        return new PersistentVector_Transient($vec->count(),
            $vec->UNSAFE_getShift(),
            self::editableRoot($vec->UNSAFE_getRoot()),
            $tail);
    }

    static function editableRoot($root)
    {
        static $ctr = 0;
        $ctr++;
        $newRoot = $root->array;
        return new PersistentVector_Node($ctr, $newRoot);
    }

    static function editableTail($tail)
    {
        return $tail;
    }

    private function ensureEditable()
    {
        if ($this->root->edit == null) {
            throw new \Exception("Transient used after persistent call");
        }
    }

    private function ensureEditableNode($node)
    {
        if ($this->root->edit === $node->edit)
            return $node;

        $newarr = $node->array;
        return new PersistentVector_Node($this->root->edit, $newarr);
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


    function conj($val)
    {
        $this->ensureEditable();
        $root = $this->root;
        $tail = &$this->tail;
        $i = $this->count;

        if ($i - $this->tailOff() < 32) {
            $tail[$i & 0x01f] = $val;
            $this->count++;
            return $this;
        }

        $newroot = null;
        $tailnode = new PersistentVector_Node($root->edit, $tail);
        $this->tail = array_fill(0, 32, null);
        $tail = &$this->tail;
        $tail[0] = $val;
        $newshift = $this->shift;
        if (Util::uRShift($this->count, 5) > (1 << $this->shift)) {
            $arr = array_fill(0, 32, null);
            $newroot = new PersistentVector_Node($root->edit, $arr);
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

        $trimmedTail = array_slice($this->tail, 0, $this->count - $this->tailOff());
        $this->edit = false;
        return new PersistentVector($this->count, $this->shift, $this->root, $trimmedTail);
    }

    private function newPath($edit, $level, $node)
    {
        if ($level == 0)
            return $node;
        $newarr = array_fill(0, 32, null);
        $ret = new PersistentVector_Node($edit, $newarr);
        $ret->array[0] = $this->newPath($edit, $level - 5, $node);
        return $ret;
    }

    function doAssoc($level, $node, $i, $val)
    {
        $node = $this->ensureEditableNode($node);
        $ret = $node;
        if($level == 0)
        {
            $ret->array[$i & 0x01f] = $val;
        }
        else {
            $subidx = Util::uRShift($i, $level) & 0x01f;
            $ret->array[$subidx] = $this->doAssoc($level - 5, $node->array[$subidx], $i, $val);
        }
        return $ret;
    }

    function assocN($i, $val)
    {
        $cnt = $this->count;
        $this->ensureEditable();
        if($i >= 0 && $i < $cnt){
            if($i >= $this->tailOff()){
                $this->tail[$i & 0x01f] = $val;
                return $this;
            }
            $this->root = $this->doAssoc($this->shift, $this->root, $i, $val);
            return $this;
        }
        if($i == $cnt) return $this->conj($val);
        throw new \OutOfBoundsException();
    }

    function assoc($key, $val)
    {
        return $this->assocN($key, $val);
    }

    private function popTail($level, PersistentVector_Node $node){
        $node = $this->ensureEditableNode($node);
        $subidx = Util::uRShift($this->count - 2, 0x01f);
        if($level > 5){
            $newchild = $this->popTail($level - 5, $node->array[$subidx]);
            if($newchild == null && $subidx == 0){
                return null;
            }
            else {
                $ret = $node;
                $ret->array[$subidx] = $newchild;
                return $ret;
            }
        }
        else if ($subidx == 0){
            return null;
        }
        else {
            $ret = $node;
            $ret->array[$subidx] = null;
            return $ret;
        }
    }

    function pop()
    {
        $this->ensureEditable();
        $cnt = $this->count;
        if($cnt == 0) throw new \Exception("Can't pop empty vector");
        if($cnt == 1) {
            $this->count = 0;
            return $this;
        }

        $i = $cnt - 1;
        if($i & 0x01f > 0){
            --$this->count;
            return $this;
        }


        $newTail = $this->editableArrayFor($cnt - 2);
        $newRoot = $this->popTail($this->shift, $this->root);
        $newShift = $this->shift;
        if($newRoot == null){
            $newRoot = new PersistentVector_Node($this->root->edit, array_fill(0, 32, null));
        }
        if($this->shift > 5 && $newRoot->array[1] == null){
            $newRoot = $this->ensureEditableNode($newRoot->array[0]);
            $newShift -= 5;
        }
        $this->root = $newRoot;
        $this->shift = $newShift;
        $this->count--;
        $this->tail = $newTail;

        return $this;
    }

    function arrayFor($i){
        if ( $i >= 0 && $i < $this->count){
            if ( $i >= $this->tailOff()){
                return $this->tail;
            }
            $node = $this->root;
            for($level = $this->shift; $level > 0; $level -= 5){
                $node = $node->array[Util::uRShift($i, $level) & 0x01f];
            }
            return $node->array;
        }
        throw new \OutOfBoundsException();
    }

    function editableArrayFor($i){
        if ( $i >= 0 && $i < $this->count){
            if ( $i >= $this->tailOff()){
                return $this->tail;
            }
            $node = $this->root;
            for($level = $this->shift; $level > 0; $level -= 5){
                $node = $this->ensureEditableNode($node->array[Util::uRShift($i, $level) & 0x01f]);
            }
            return $node->array;
        }
        throw new \OutOfBoundsException();
    }


    function nth($i)
    {
        $this->ensureEditable();
        $node = $this->arrayFor($i);
        return $node[$i & 0x01f];
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
    
    function __invoke()
    {
        $args = func_get_args();
        if(count($args) == 1){
            return $this->nth($args[0]);
        }
        if(count($args) == 2){
            return $this->nthOr($args[0], $args[1]);
        }
        throw new \Exception("Arity error. Expected 1 or 2 args.");
    }

    public function offsetExists($offset)
    {
        return $offset >= 0 && $offset < $this->count();
    }

    public function offsetGet($offset)
    {
        return $this->nth($offset);
    }

    public function offsetSet($offset, $value)
    {
        throw new \Exception();
    }

    public function offsetUnset($offset)
    {
        throw new \Exception();
    }
}