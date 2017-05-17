<?hh // strict
namespace Pando;
use Facebook\HackRouter\{
	RequestParameters,
	BaseRouter,
	HttpMethod
};
// type RenderTree<Tk, Tv> = AbstractFKT<Tk, Tv>; // the `string` has immense importance here: it dictates the final type of the result. If we're using XHP, `string` will be replaced by XHPRoot. (replaced by generic Tv, to keep things general for the meantime. Maybe there's such thing as a __toStringable?)
// consider wrapping an FKT, and injecting the information into it at dispatch-time.
// nah, then it just re-becomes Dispatcher. We can't get around needing that route information.
class Dispatcher<Tx as arraykey, Tv as \Stringish, -TState as State\State<Tx, Tv>> extends BaseRouter<BaseRoute<Tx, Tv, TState>> {
	private ImmMap<HttpMethod, ImmMap<string, BaseRoute<Tx, Tv, TState>>> $routes; // method -> route string -> route
	                                                            //                             └── dependency -> this
	private ?BaseRoute<Tx, Tv, TState> $default;
	public function __construct(Iterable<BaseRoute<Tx, Tv, TState>> $flat_routes) {
		$routes = Map{}; // ImmMap<HTTPMethod, ImmMap<string, (UriPattern, (function(RequestParameters, ImmMap<string, Awaitable<string>>, Database): \Stringish))>>
		// AKA ImmMap<HTTPMethod, ImmMap<string, classname<WebController>>>, but without anonymous classes, this is totally impractical
		foreach($flat_routes as $route) {
			if($routes->containsKey($route::get_method()))
				$routes[$route::get_method()]->set($route->getFastRoutePattern(), $route);
			else
				$routes->set($route::get_method(), Map{ $route->getFastRoutePattern() => $route });
		}
		
		$immutable_routes = Map{};
		foreach($routes as $k => $route) {
			$immutable_routes[$k] = $route->immutable();
		}
		$this->routes = $immutable_routes->immutable();
	}
	
	public function getRoutes(): ImmMap<HttpMethod, ImmMap<string, BaseRoute<Tx, Tv, TState>>> {
		return $this->routes;
	}
}