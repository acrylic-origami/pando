<?hh // strict
namespace Pando;

use Facebook\HackRouter\HttpMethod;
use Facebook\HackRouter\HasUriPattern;
use Facebook\HackRouter\RequestParameters;
use Facebook\HackRouter\GetFastRoutePatternFromUriPattern;

abstract class Route<+Tv, Tx as arraykey, TQuery, TDb as Database<TQuery>> implements HasUriPattern { // Scala, you're making me too jealous. Database<_> :(
  use GetFastRoutePatternFromUriPattern;
	// extends \HHRx\Tree\AbstractFutureKeyedTree<(Tv, ?arraykey), Tx>
	// extends \HHRx\Tree\UnresolvedTree<(Tv, ?arraykey), Tx>
	private ?TDb $db = null;
	abstract public static function get_method(): HttpMethod;
	public function __construct(
		protected UriPattern $uri,
		private (function(RequestParameters, ConstMap<Tx, Awaitable<\Stringish>>, TDb): Awaitable<ComparableView>) $resolver,
		private \ConstMap<Tx, Dispatcher<Tv, Tx, TQuery, TDb, this>> $dependencies = Map{},
	) {}
	public function set_database(TDb $db): void {
		$this->db = $db;
		foreach($this->dependencies as $dispatcher) {
			$dispatcher->set_database($db);
		}
	}
	public function get_uri(): string {
		return $this->uri;
	}
	public function get_dependencies(): \ConstMap<Tx, Dispatcher<Tv, Tx, TQuery, TDb, this>> {
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
		$db->dig($this->get_uri());
		
		$resolved_dependencies = $this->get_dependencies()->keyed_reduce((?MapIA<Tx, ViewTree<Tv, Tx>> $prev, Pair<Tx, Dispatcher<Tv, Tx, this>> $k_v) ==> {
			list($k, $dispatcher) = $k_v;
			$route = $dispatcher->dispatch($method, $uri);
			$resolved_dependency = $route->resolve($method, $uri);
			return $prev->set($k, $resolved_dependency);
		}, new Map());
		$resolved_dependency_values = $resolved_dependencies->keyed_reduce((?MapIA<Tx, (Tv, ?arraykey)> $prev, Pair<Tx, ViewTree<Tv, Tx>> $k_v) ==> {
			list($k, $subtree) = $k_v;
			$v = $subtree->get_v();
			invariant(!is_null($prev), 'Implementation error: non-null `MapIA` passed into non-null `reduce`, but null value obtained during reduction.');
			if(!is_null($v))
				$prev->set($k, $v);
			return $prev;
		}, new Map());
		
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