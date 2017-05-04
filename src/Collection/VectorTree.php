<?hh // strict
namespace Pando\Collection;
class VectorTree<T> implements Tree<int, T>, \IteratorAggregate<T> {
	public function __construct(
		private \Vector<this> $forest,
		private ?T $v = null
		) {}
	
	public function get_v(): ?T {
		return $this->v;
	}
	
	public function get_subtree(int $k): ?this {
		return $this->forest->get($k);
	}
	
	public function get_forest(): Vector<this> {
		return $this->forest;
	}
	
	public function set_subtree(int $k, this $incoming): void {
		$this->forest->set($k, $incoming);
	}
	
	public function add_subtree(this $incoming): void {
		$this->forest->add($incoming);
	}
	
	public function getIterator(): Iterator<T> {
		if(!is_null($this->v))
			yield $this->v;
		
		foreach($this->forest as $subtree) {
			foreach($subtree as $v)
				yield $v;
		}
	}
}