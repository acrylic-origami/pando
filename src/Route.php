<?hh // strict
namespace Pando;
use \Pando\Dispatcher;
use \HHRx\Tree\Tree;
use \HHRx\Tree\ViewTree;
use \HHRx\Util\Collection\KeyedContainerWrapper as KC;
use \HHRx\Util\Collection\IterableConstIndexAccess as IterableCIA;
use \HHRx\Util\Collection\ConstMapCIA;
use \HHRx\Util\Collection\MapIA;
abstract class Route<+Tv, Tx as arraykey> implements \HHRx\Streamlined<\PandoDB\IdentifierCollection> {
	// extends \HHRx\Tree\AbstractFutureKeyedTree<(Tv, ?arraykey), Tx>
	// extends \HHRx\Tree\UnresolvedTree<(Tv, ?arraykey), Tx>
	private ConstMapCIA<Tx, Dispatcher<Tv, Tx, this>> $dependencies;
	private ?Database $db = null;
	protected ?\HHRx\Stream<\PandoDB\IdentifierCollection> $local_stream = null;
	public function __construct(
		protected string $path,
		private (function(IterableCIA<Tx, (Tv, ?arraykey), \ConstMap<Tx, (Tv, ?arraykey)>>): (Tv, ?arraykey)) $resolver, // top-level resolver creates both a view and a score - a number representing the view in some comparable domain
		?ConstMapCIA<Tx, Dispatcher<Tv, Tx, this>> $dependencies = null,
	) {
		if(is_null($dependencies))
			$this->dependencies = new \HHRx\Util\Collection\ConstMapCIA();
		else
			$this->dependencies = $dependencies;
	}
	public function set_database(Database $db): void {
		$this->db = $db;
		foreach($this->dependencies as $dispatcher) {
			$dispatcher->set_database($db);
		}
	}
	public function get_path(): string {
		return $this->path;
	}
	public function get_dependencies(): IterableCIA<Tx, Dispatcher<Tv, Tx, this>, \ConstMap<Tx, Dispatcher<Tv, Tx, this>>> {
		return $this->dependencies;
	}
	public function get_local_stream(): \HHRx\Stream<\PandoDB\IdentifierCollection> {
		invariant(!is_null($this->local_stream), 'Attempting to retrieve stream from unresolved route.');
		return $this->local_stream;
	}
	public function resolve(string $method, string $uri): ViewTree<Tv, Tx> {
		$resolver = $this->resolver;
		
		$db = $this->db;
		invariant(!is_null($db), 'Database not set at top level or did propagate properly to non-root nodes in view tree.');
		$db->dig($this->get_path());
		
		$substreams = Vector{};
		
		$resolved_dependencies = $this->get_dependencies()->keyed_reduce((?MapIA<Tx, ViewTree<Tv, Tx>> $prev, Pair<Tx, Dispatcher<Tv, Tx, this>> $k_v) ==> {
			list($k, $dispatcher) = $k_v;
			$route = $dispatcher->dispatch($method, $uri);
			$resolved_dependency = $route->resolve($method, $uri);
			
			// aggregate streams from lower routes
			$local_stream = $route->get_local_stream();
			if(!is_null($local_stream))
				$substreams->add($local_stream);
			invariant(!is_null($prev), 'Implementation error: non-null `MapIA` passed into non-null `reduce`, but null value obtained during reduction.');
			return $prev->set($k, $resolved_dependency);
		}, new MapIA(Map{}));
		$resolved_dependency_values = $resolved_dependencies->keyed_reduce((?MapIA<Tx, (Tv, ?arraykey)> $prev, Pair<Tx, ViewTree<Tv, Tx>> $k_v) ==> {
			list($k, $subtree) = $k_v;
			$v = $subtree->get_v();
			invariant(!is_null($prev), 'Implementation error: non-null `MapIA` passed into non-null `reduce`, but null value obtained during reduction.');
			if(!is_null($v))
				$prev->set($k, $v);
			return $prev;
		}, new MapIA(Map{}));
		
		$local_stream = $db->get_current()->get_v();
		if(!is_null($local_stream))
			$substreams->add($local_stream);
		$this->local_stream = \HHRx\KeyedStream::merge_all($substreams);
		$db->surface();
		return new ViewTree($resolved_dependencies, $resolver($resolved_dependency_values));
	}
	// public function get_resolver(): (function(KC<>): Awaitable<(Tv, ?arraykey)>) {
	// 	// impossible return
	// 	return $this->resolver;
	// }
	
	// Allow Pando\Dispatcher to store and deal with the resolver.
	// public function _resolve(): Awaitable<(Tv, ?arraykey)> {
	// 	$resolver = $this->resolver;
	// 	return $resolver($this);
	// }
}