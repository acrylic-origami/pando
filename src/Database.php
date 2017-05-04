<?hh // strict
namespace Pando;
use Pando\Collection\MapTree;
newtype QueryTree<TQuery> as \IteratorAggregate<Pair<string, Vector<TQuery>>> = MapTree<string, Vector<TQuery>>;
// newtype ConstQueryTree<+TQuery> = Tree<string, \ConstVector<TQuery>>;
abstract class Database<TQuery> {
	private QueryTree<TQuery> $queries;
	private Vector<QueryTree<TQuery>> $path;
	public function __construct() {
		$this->queries = new MapTree(Map{}, Vector{});
		$this->path = Vector{};
	}
	public function dig(string $child_key): void {
		/* HH_FIXME[4110] We know that $this->queries is exactly MutableTree by the constructor and no other methods modifying it (i.e. it is `const`). The [4110] error reflects the non-const-ness. */
		$this->queries->set_subtree($child_key, new MapTree(Map{}, Vector{}));
		$child = $this->queries->get_subtree($child_key);
		invariant(!is_null($child), 'Wat, I just set it.');
		$this->path->add($child);
	}
	public function surface(): void {
		if(!$this->path->isEmpty())
			$this->path->pop();
	}
	private function _get_current_queries(): Vector<TQuery> {
		$current = $this->path->lastValue() ?? $this->queries;
		$queries = $current->get_v();
		invariant(!is_null($queries), 'Unexpected null query list. Implementation error, or unset by nefarious outer forces.');
		return $queries;
	}
	protected function _log_query(TQuery $query): void {
		$this->_get_current_queries()->add($query);
	}
}