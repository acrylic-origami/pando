<?hh // strict
namespace Pando\State;

use Pando\{
	BaseRoute,
	Dispatcher,
	ComparableView
};
use Facebook\HackRouter\{
	HttpMethod,
	RequestParameters
};

interface IStateFactory<Tx as arraykey, Tv as \Stringish, +TState as State<Tx, Tv>> {
	// preferably, we wouldn't even need BaseComparableView, but there is no way to write down the topmost ComparableView
	public function make(BaseRoute<Tx, Tv, TState> $context, string $path): TState;
}
class StateFactory<Tx as arraykey, Tv as \Stringish> implements IStateFactory<Tx, Tv, State<Tx, Tv>> {
	public function make(BaseRoute<Tx, Tv, State<Tx, Tv>> $context, string $path): State<Tx, Tv> {
		return new State($context, $path);
	}
}
class State<Tx as arraykey, +Tv as \Stringish> {
	private \ConstMap<Tx, Dispatcher<Tx, Tv, this>> $dependencies;
	private HttpMethod $method;
	public function __construct(BaseRoute<Tx, Tv, this> $context, protected string $path) {
		$this->dependencies = $context->get_dependencies();
		$this->method = $context::get_method();
	}
	<<__Memoize>>
	public async function at(Tx $k): Awaitable<Tv> {
		$dispatcher = $this->dependencies->at($k);
		list($route, $params) = $dispatcher->routeRequest($this->method, $this->path);
		$view_wrapper = await $route->render(new RequestParameters($route->getUriPattern()->getParameters(), ImmVector{}, $params), $this->path);
		return $view_wrapper->get_view();
	}
}

// existential + implicit would cut 5 to 3 :(
abstract class DatabaseStateFactory<Tx as arraykey, Tv as \Stringish, TQuery, +TState as DatabaseState<Tx, Tv, TQuery>> implements IStateFactory<Tx, Tv, TState> {
	/* HH_IGNORE_ERROR[4110] This is a limitation of the typechecker (this override should be valid because we're subtyping TState which is in a covariant position here): see https://github.com/facebook/hhvm/issues/7818 */
	abstract public function make(BaseRoute<Tx, Tv, TState> $context, string $path): TState;
}