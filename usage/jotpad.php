<?hh // decl
abstract class FKT<Tk, Tv, TResolver> {
	protected ?Map<Tk, Awaitable<this>> $subtree;
	protected ?Tv $v;
	public function put(?Map<Tk, Awaitable<this>> $subtree): this {
		$this->subtree = $subtree;
		return $this; // for chaining
	}
	public async function get(Tk $k): Awaitable<this> { 
		if(!is_null($this->subtree))
			if($this->subtree->containsKey($k))
				return await $this->subtree[$k]->resolve();
			else
				throw new \OutOfBoundsException('Key `'.$k.'` not found in FKT::get.');
		else
			throw new \BadMethodCallException('FKT::subtree is null.');
	}
	public async function resolve(TResolver $arg): Awaitable<Tv> {
		return $this->v ?? ($this->v = await $this->_resolve($arg)); // the caching function
	}
	abstract protected async function _resolve(TResolver $arg): Awaitable<Tv>; // `get()` can make use of $this->subtree as the argument to develop its values
		// this is the method defined by anonymous classes that represent routes.
}
// abstract class FKT_Route<Tk, Tv, TResolver> extends FKT<Tk, Tv, TResolver> {
// 	// public function nest(Map<Tk, Map<Tk, FKT<Tk, Tv>>> $batch): this {
// 	// 	//                 ↑       ↑       ↑
// 	// 	//     Map of dependencies |       |
// 	// 	//                  Map of routes  |
// 	// 	//       Specific routing functionality via FKT for that route
// 	// 	return $this; // for chaining
// 	// }
// 	public function nest(Map<Tk, Dispatcher<Tk, Tv>>)
// }                                          ↓ check if `void` here is valid. Dispatcher is trying to make an assertion that the routes won't need additional information at render-time.
class Dispatcher<Tk, Tv> extends FKT<Tk, FKT<Tk, Tv, void>, (string, string)> { // beware: I was tempted to write FKT<Tk, Awaitable<FKT<Tk, Tv>>>, but FKT already has `Awaitable<this>` which takes care of that Awaitable
	// public KT<Tk, Awaitable<Tv>> $batches;
	// protected (string, string) $route; // method, URI // reminiscent of a time when I didn't [want to] have TResolver
	
	const type RouteTree = FKT<Tk, Tv, void>; // if this compiles correctly, replace all clunky `FKT<Tk, Tv, void>`s with `RouteTree`.
	
	public function __construct(Map<arraykey, (FKT<Tk, Tv, void>, Map<arraykey, this>)> $routes, 
		protected \FastRoute\simpleDispatcher<FKT<Tk, Tv, void>> $fdispatcher // I wish anonymous _classes_ were first-class citizens so this could return anonymous classes to be constructed whenever, but alas I must include a utility function in FKT for this (now called "put")
		// alternatively: I could construct a route on the fly with an anonymous class, binding $fn to that. Then, instead of dealing in `FKTs<Tk, Tv, void>`s, I would be dealing in `function(TResolver $arg): Awaitable<Tv>`s. See '*****' for implementation
		) {
		$this->subtree = $routes->map(((FKT<Tk, Tv, void>, this) $route) ==> $route[1]);
		foreach($routes as $k=>$route) {
			$fdispatcher->add($k, $route[0]);
		}
	}
	
	// ***** 
	// Implementation of TResolveFn-based Dispatcher
	
	// const type TResolveFn = function(void $arg): Awaitable<Tv>;
	// public function __construct(Map<arraykey, (TResolveFn, Map<arraykey, this>)>) {
	// 	$this->subtree = $routes->map(($route) ==> $route[1]);
	// 	foreach($routes as $k=>$route) {
	// 		$fdispatcher->add($k, $route[0]);
	// 	}
	// }
	// public function _resolve((string, string) $route): Awaitable<TResolveFn> {
	// 	$dispatched = $this->fdispatcher->dispatch($route[0], $route[1]);
	// 	if(!is_null($dispatched))
	// 		return $dispatched->put(Asio\m($this->subtree->map((this $sub_dispatcher) ==> new class extends RouteTree {
	// 			public async function _resolve(TResolve $arg): Awaitable<Tv> {
	// 				return await (($sub_dispatcher->resolve($route))->bindTo($this))($arg);
	// 			}
	// 		})));
	// 	else
	// 		throw new \BadMethodCallException('Could not dispatch route `'.$route.'`: does not exist.');
	// }
	
	// DEPRECATED
	// public function nest(Map<Tk, this>): this { // the `this` `nest()` returns is _THIS OBJECT_, while the `this` it takes as an argument are best described as "sub_dispatchers" which return the dependendent routes
	// 	return $this; // for chaining
	// }
	
	protected async function _resolve((string, string) $route): Awaitable<FKT<Tk, Tv, void>> { // or is it FKT<Tk, Tv, void> sans Awaitable?
		// return ($this->fdispatcher->dispatch($route[0], $route[1]))(Asio\m($this->subtree->map((Awaitable<this> $sub_dispatcher) ==> $sub_dispatcher))); // if only classes were first-class citizens. see above.
		$dispatched = $this->fdispatcher->dispatch($route[0], $route[1]);
		if(!is_null($dispatched))
			return $dispatched->put(HH\Asio\m($this->subtree->map((this $sub_dispatcher) ==> $sub_dispatcher->resolve($route)))); // although `put` is not aync, `Asio\m()` is, making this function an async eventually returning an FKT<Tk, Tv>.
		else
			throw new \BadMethodCallException('Could not dispatch route `'.$route.'`: does not exist.');
	}
}
// class Batch<Tk, Tv> {
// 	public function nest(Map<Tk, Map<Tk, FKT<Tk, Tv>>>): this {
// 	}
// }

// @ root: $v = await (await $dispatcher->dispatch())->resolve();