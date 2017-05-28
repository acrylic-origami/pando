<?hh // strict
namespace Pando\State;
use Pando\{
	BaseRoute,
	Collection\MapTree
};

use Facebook\HackRouter\HttpMethod;
newtype QueryTree<TQuery> as \IteratorAggregate<Pair<string, Vector<TQuery>>> = MapTree<string, Vector<TQuery>>;
// newtype ConstQueryTree<+TQuery> = Tree<string, \ConstVector<TQuery>>;
abstract class DatabaseState<Tx as arraykey, TQuery> extends State<Tx, ViewTree> { // it's database + stashing, which is certainly not general, but let's inject the opinion here
	private QueryTree<TQuery> $queries;
	private Vector<QueryTree<TQuery>> $query_path = Vector{};
	private Map<Tx, Map<HttpMethod, Map<string, ViewTree>>> $stashed_viewforest = Map{};
	public function __construct(
		BaseRoute<Tx, Tv, this> $context,
		string $path
		) {
		parent::__construct($context, $path);
		$this->queries = new MapTree(Map{}, Vector{});
	}
	public async function at(Tx $k): Awaitable<ViewTree> {
		$view = parent::at($k);
		$stashed_viewforest->set($k, Map{ $this->method => Map{ $this->context->getFastRoutePattern() => $view } }); // note the overwriting here
		return $view;
	}
	public function get_stashed_viewforest(): Map<Tx, Map<HttpMethod, Map<string, ViewTree>>> {
		return $this->stashed_viewforest; // should clone? Think upon it; will this state be reused?
	}
	public function dig(string $child_key): void {
		/* HH_FIXME[4110] We know that $this->queries is exactly MutableTree by the constructor and no other methods modifying it (i.e. it is `const`). The [4110] error reflects the non-const-ness. */
		$this->queries->set_subtree($child_key, new MapTree(Map{}, Vector{}));
		$child = $this->queries->get_subtree($child_key);
		invariant(!is_null($child), 'Wat, I just set it.');
		$this->query_path->add($child);
	}
	public function surface(): void {
		if(!$this->query_path->isEmpty())
			$this->query_path->pop();
	}
	private function _get_current_queries(): Vector<TQuery> {
		$current = $this->query_path->lastValue() ?? $this->queries;
		$queries = $current->get_v();
		invariant(!is_null($queries), 'Unexpected null query list. Implementation error, or unset by nefarious outer forces.');
		return $queries;
	}
	protected function _log_query(TQuery $query): void {
		$this->_get_current_queries()->add($query);
	}
}