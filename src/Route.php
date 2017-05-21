<?hh // strict
namespace Pando;

use Pando\State\IStateFactory;

use Facebook\HackRouter\{
	UriPattern,
	HttpMethod,
	HasUriPattern,
	RequestParameters,
	GetFastRoutePatternFromUriPattern
};

abstract class Route<Tx as arraykey, Tv as \Stringish, -TState as State\State<Tx, Tv>, +TComparable as ComparableView<Tv, TComparable>> implements BaseRoute<Tx, Tv, TState> {
	// extends \HHRx\Tree\AbstractFutureKeyedTree<(Tv, ?arraykey), Tx>
	// extends \HHRx\Tree\UnresolvedTree<(Tv, ?arraykey), Tx>
	private ?TComparable $stashed_view = null;
	public function __construct(
		protected UriPattern $uri,
		private (function(RequestParameters, TState): Awaitable<TComparable>) $resolver,
		private IStateFactory<Tx, Tv, TState> $state_factory,
		private \ConstMap<Tx, Dispatcher<Tx, Tv, TState>> $dependencies = Map{},
	) {}
	
	// Dynamic version of hack-router HasUriPattern + GetFastRoutePatternFromUriPattern
	public function getUriPattern(): UriPattern {
		return $this->uri;
	}
	public function getFastRoutePattern(): string {
		return $this->getUriPattern()->getFastRouteFragment();
	}
	
	protected function get_resolver(): (function(RequestParameters, TState): Awaitable<TComparable>) {
		return $this->resolver;
	}
	
	public function get_dependencies(): \ConstMap<Tx, Dispatcher<Tx, Tv, TState>> {
		return $this->dependencies;
	}
	
	public function render(RequestParameters $params, string $path): Awaitable<TComparable> {
		$resolver = $this->resolver;
		return $resolver($params, $this->state_factory->make($this, $path));
	}
	
	public async function rerender_and_compare(RequestParameters $params, string $path): Awaitable<?TComparable> {
		$stashed_view = $this->stashed_view;
		invariant(!is_null($stashed_view), 'The view was not rendered before trying to rerender.');
		
		$resolver = $this->resolver;
		$new_view = await $resolver($params, $this->state_factory->make($this, $path));
		if($stashed_view->compare($new_view))
			return $new_view;
		else
			return null;
	}
	// public function resolve(string $method, string $uri): ViewTree<Tv, Tx> {
	// 	$resolver = $this->resolver;
		
	// 	$db = $this->db;
	// 	invariant(!is_null($db), 'Database not set at top level or did propagate properly to non-root nodes in view tree.');
	// 	$db->dig($this->get_uri());
		
	// 	$resolved_dependencies = $this->get_dependencies()->keyed_reduce((?MapIA<Tx, ViewTree<Tv, Tx>> $prev, Pair<Tx, Dispatcher<Tv, Tx, this>> $k_v) ==> {
	// 		list($k, $dispatcher) = $k_v;
	// 		$route = $dispatcher->dispatch($method, $uri);
	// 		$resolved_dependency = $route->resolve($method, $uri);
	// 		return $prev->set($k, $resolved_dependency);
	// 	}, new Map());
	// 	$resolved_dependency_values = $resolved_dependencies->keyed_reduce((?MapIA<Tx, (Tv, ?arraykey)> $prev, Pair<Tx, ViewTree<Tv, Tx>> $k_v) ==> {
	// 		list($k, $subtree) = $k_v;
	// 		$v = $subtree->get_v();
	// 		invariant(!is_null($prev), 'Implementation error: non-null `MapIA` passed into non-null `reduce`, but null value obtained during reduction.');
	// 		if(!is_null($v))
	// 			$prev->set($k, $v);
	// 		return $prev;
	// 	}, new Map());
		
	// 	$db->surface();
	// 	return new ViewTree($resolved_dependencies, $resolver($resolved_dependency_values));
	// }
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