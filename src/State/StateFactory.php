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

interface IStateFactory<+TState as State> {
	// preferably, we wouldn't even need BaseComparableView, but there is no way to write down the topmost ComparableView
	public function make(BaseRoute<TState> $context, string $path): TState;
}
class StateFactory implements IStateFactory<Tx, Tv, State<Tx, Tv>> {
	public function make(BaseRoute<Tx, Tv, State<Tx, Tv>> $context, string $path): State<Tx, Tv> {
		return new State($context, $path);
	}
}
class State {
	/* HH_FIXME[4120] Intending to use $context in object-protected way */
	public function __construct(protected BaseRoute<Tx, Tv, this> $context, protected string $path) {}
	
	public async function at<TExistential as ComparableView<ViewTree<TExistential>, TExistential>(Tx $k): Awaitable<ViewTree<TExistential>> {
		$dispatcher = $this->context->get_dependencies()->at($k);
		list($route, $params) = $dispatcher->routeRequest($this->context::get_method(), $this->path);
		$view_wrapper = await $route->render(new RequestParameters($route->getUriPattern()->getParameters(), ImmVector{}, $params), $this->path);
		return $view_wrapper->get_view();
	}
}

// existential + implicit would cut 5 to 3 :(
abstract class DatabaseStateFactory<TQuery, +TState as DatabaseState<Tx, TQuery>> implements IStateFactory<TState> {
	/* HH_IGNORE_ERROR[4110] This is a limitation of the typechecker (this override should be valid because we're subtyping TState which is in a covariant position here): see https://github.com/facebook/hhvm/issues/7818 */
	abstract public function make(Route<Tx, TState> $context, string $path): TState;
}