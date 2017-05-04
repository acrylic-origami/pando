<?hh // strict
namespace Pando\Collection;
class MapTree<Tk as arraykey, Tv> implements Tree<Tk, Tv>, \IteratorAggregate<Pair<Tk, Tv>> {
	public function __construct(
		private Map<Tk, this> $forest = Map{},
		private ?Tv $v = null
		) {
	}
	
	public function get_v(): ?Tv {
		return $this->v;
	}
	
	public function get_subtree(Tk $k): ?this {
		return $this->forest->get($k);
	}
	
	public function get_forest(): Map<Tk, this> {
		return $this->forest;
	}
	
	public function set_subtree(Tk $k, this $incoming): void {
		$this->forest->set($k, $incoming);
	}
	
	public function getIterator(): Iterator<Pair<Tk, Tv>> {
		foreach($this->forest as $subtree_k => $subtree) {
			$subtree_v = $subtree->get_v();
			if(!is_null($subtree_v))
				yield Pair{ $subtree_k, $subtree_v };
			
			foreach($subtree as $v)
				yield $v;
		}
	}
}