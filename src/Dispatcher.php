<?hh // strict
namespace Pando;
use Pando\Route;
use Pando\Tree\KeyedTree as KT;
// use Pando\Tree\Tree;
use Pando\Tree\UnresolvedTree;
use Pando\Tree\AbstractFutureKeyedTree as AbstractFKT;
use Pando\Util\Collection\KeyedContainerWrapper as KC;
// type RenderTree<Tk, Tv> = AbstractFKT<Tk, Tv>; // the `string` has immense importance here: it dictates the final type of the result. If we're using XHP, `string` will be replaced by XHPRoot. (replaced by generic Tv, to keep things general for the meantime. Maybe there's such thing as a __toStringable?)
// consider wrapping an FKT, and injecting the information into it at dispatch-time.
// nah, then it just re-becomes Dispatcher. We can't get around needing that route information.
class Dispatcher<+Tv, +Tk as arraykey, +TRoute as Route\Route<Tv, Tk>> {
	private KC<Tk, KC<Tk, TRoute>> $search_tree; // method -> route string -> route
	                                             //                             └── dependency -> this
	private ?Route\Route<Tv, Tk> $default;
	private \FastRoute\Dispatcher $_dispatcher;
	public function __construct(Iterable<TRoute> $routes, (function((function(\FastRoute\RouteCollector): void)): \FastRoute\Dispatcher) $fdispatcher = fun('\FastRoute\simpleDispatcher')) {
		$this->_dispatcher = $fdispatcher((\FastRoute\RouteCollector $r) ==> {
			foreach($routes as $route) {
				if($route instanceof Route\Default && is_null($this->default)) 
					$this->default = $route;
				elseif($route instanceof Route\PathedRoute)
					$r->addRoute(\Pando\Util\Class\classname($route), $route->path, $route);
			}
		});
	}
	
	<<__Memoize>> // not totally sold on this: what if $this->subtree changes? Will have to find a way around this if it ever arises.
	public function dispatch(string $method, string $uri): Route\Route<Tv, Tk> { // : UnresolvedTree<Tv, Tk>
		$dispatched = $this->_dispatcher->dispatch($method, $uri); // this should be a shape >_>
                                                       // consider Hackifying FastRoute to make this array a shape
		if($dispatched[0] === \FastRoute\Dispatcher::FOUND)
			$route = $dispatched[1][1];
		elseif(!is_null($this->default))
			$route = $this->default;
		else {
			throw new \BadMethodCallException(sprintf('No routes match `%s` request for `%s` path and no default available for path.', $method, $uri));
		}
		return $route;
		// $dependencies = $route->get_dependencies();
		// if(!is_null($dependencies))
		// 	return new UnresolvedTree($dependencies->map((this $subdispatcher) ==> $subdispatcher->dispatch($method, $uri)), $route->get_resolver());
		// else
		// 	return new UnresolvedTree(new KC(null), $route->get_resolver());
		// return new AbstractFKT($dispatched[1][0], $route->get_dependencies()->map((this $subdispatcher) ==> $subdispatcher->dispatch($method, $uri)));
	}
}