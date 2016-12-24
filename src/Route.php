<?hh // strict
namespace Pando\Route;
use \Pando\Dispatcher;
use \Pando\Tree\Tree;
use \Pando\Util\Collection\KeyedContainerWrapper as KC;
abstract class Route<+Tv, -Tx as arraykey> {
	// extends \Pando\Tree\AbstractFutureKeyedTree<(Tv, ?arraykey), Tx>
	// extends \Pando\Tree\UnresolvedTree<(Tv, ?arraykey), Tx>
	private KC<Tx, Dispatcher<Tv, Tx, this>> $dependencies;
	public function __construct(
		private (function(KC<Tx, (Tv, ?arraykey)>): (Tv, ?arraykey)) $resolver, // top-level resolver creates both a view and a score - a number representing the view in some comparable domain
		?KeyedContainer<Tx, Dispatcher<Tv, Tx, this>> $dependencies) {
		$this->dependencies = new KC($dependencies);
	}
	public function get_dependencies(): KC<Tx, Dispatcher<Tv, Tx, this>> {
		return $this->dependencies;
	}
	public function resolve(string $method, string $uri): Tree<(Tv, ?arraykey), Tx> {
		$resolver = $this->resolver;
		$resolved_dependencies = $this->get_dependencies()->map((Dispatcher<Tv, Tx, this> $dispatcher) ==> $dispatcher->dispatch($method, $uri)->resolve($method, $uri));
		return new Tree($resolved_dependencies, $resolver($resolved_dependencies));
	}
	// public function get_resolver(): (function(KC<>): Awaitable<(Tv, ?arraykey)>) {
	// 	// impossible return
	// 	return $this->resolver;
	// }
	
	// Allow Pando\TreeRouter\Dispatcher to store and deal with the resolver.
	// public function _resolve(): Awaitable<(Tv, ?arraykey)> {
	// 	$resolver = $this->resolver;
	// 	return $resolver($this);
	// }
}