<?hh // strict
<<__ConsistentConstruct>>
class KeyedTree<Tk as arraykey, Tv> {
	protected ?Map<Tk, this> $subtree;
	public function __construct( // shockingly, this -> `<TTree as KeyedTree<Tk, Tv>>` is allowed
		protected ?Tv $root
		) {}
	public function set(?Tv $root): void {
		$this->root = $root;
	}
	public function add(Tk $k, Tv $v): void {
		if(is_null($this->subtree))
			$this->subtree = Map{ $k => new static($v) };
		else
			$this->subtree[$k] = new static($v);
	}
	public function merge_forest(Map<Tk, this> $forest, bool $copy_on_empty = true): void {
		if(is_null($this->subtree))
			if($copy_on_empty)
				$this->subtree = $forest->toMap();
			else
				$this->subtree = $forest;
		else
			foreach($forest as $k=>$tree)
				$this->subtree[$k] = $tree;
	}
	
	public function retrieve(): ?Tv {
		return $this->root; // consider null checks
	}
	public function get(Tk $k): this {
		invariant(!is_null($this->subtree), 'Leaf: has no children.');
		invariant($this->subtree->containsKey($k), 'Child '.$k.' does not exist.');
		
		return $this->subtree[$k];
	}
	public function getrieve(Tk $k): ?Tv {
		return $this->get($k)->retrieve();
	}
}