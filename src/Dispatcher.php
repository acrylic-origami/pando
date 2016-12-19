<?hh // strict
namespace Pando\TreeRouter;
use Pando\Tree\KeyedTree as KT;
use Pando\Tree\Tree;
use Pando\Tree\AbstractFutureKeyedTree as AbstractFKT;
use Pando\Util\Collection\KeyedContainerWrapper as KC;
// type RenderTree<Tk, Tv> = AbstractFKT<Tk, Tv>; // the `string` has immense importance here: it dictates the final type of the result. If we're using XHP, `string` will be replaced by XHPRoot. (replaced by generic Tv, to keep things general for the meantime. Maybe there's such thing as a __toStringable?)
// consider wrapping an FKT, and injecting the information into it at dispatch-time.
// nah, then it just re-becomes Dispatcher. We can't get around needing that route information.
class Dispatcher<+Tk as arraykey, +Tv, +TRoute as Route<Tv, Tk>> extends Tree<\FastRoute\Dispatcher, Tk> {
	private KC<Tk, KC<Tk, TRoute>> $search_tree; // method -> route string -> route
	                                             //                             └── dependency -> this
	private ?AbstractFKT<Tk, TRoute> $default;
	public function __construct((function((function(\FastRoute\RouteCollector): void)): \FastRoute\Dispatcher) $fdispatcher = fun('\FastRoute\simpleDispatcher'), TRoute ...$routes) {
		parent::__construct($fdispatcher((\FastRoute\RouteCollector $r) ==> {
			foreach($routes as $route) {
				if($route instanceof Route\Default && is_null($this->default)) 
					$this->default = new AbstractFKT($route->fn, $route->dep);
				elseif($route instanceof Route\PathedRoute) {
					$r->addRoute(\Pando\Util\Class\classname($route), $route->route, $route);
				}
			}
		}));
	}
	
	<<__Memoize>> // not totally sold on this: what if $this->subtree changes? Will have to find a way around this if it ever arises.
	public function dispatch(string $method, string $uri): AbstractFKT<Tk, Tv> { // this is essentially resolve, except with extra information needed. If only we could can resolve on Dispatcher, but alas it's needed for general FKT.
		$dispatched = $this->get_v()->dispatch($method, $uri); // this should be a shape >_>
                                                       // consider Hackifying FastRoute to make this array a shape
		if($dispatched[0] === \FastRoute\Dispatcher::FOUND) {
			$route = $dispatched[1][1];
			$this->subtree = $route; // Coming back to this and looking everything over, I don't think this step is crucial, but it's nice because in the end the dispatcher tree is the actual path of routes that was resolved
			return new AbstractFKT($dispatched[1][0], $this->subtree->map((this $subdispatcher) ==> $subdispatcher->dispatch($method, $uri)));
		}
		elseif(!is_null($this->default))
			// $this->subtree = 
			throw new \BadMethodCallException('Could not dispatch route `'.$route.'`: does not exist.');
	}
}