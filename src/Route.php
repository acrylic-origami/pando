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

abstract class Route<-TState as State\State, +TComparable as ComparableView<Tv, TComparable>> implements BaseRoute<TState> {
	// extends \HHRx\Tree\AbstractFutureKeyedTree<(Tv, ?arraykey), Tx>
	// extends \HHRx\Tree\UnresolvedTree<(Tv, ?arraykey), Tx>
	private ?TComparable $stashed_view = null;
	public function __construct(
		protected UriPattern $uri,
		private (function(RequestParameters, TState): Awaitable<TComparable>) $resolver,
		private IStateFactory<TState> $state_factory,
		private \ConstMap<string, Dispatcher<Tv, TState>> $dependencies = Map{} // \ConstMap<Tx, Dispatcher<Tx, Tv, TState>>
	) {}
	
	// Dynamic version of hack-router HasUriPattern + GetFastRoutePatternFromUriPattern
	public function getUriPattern(): UriPattern {
		return $this->uri;
	}
	public function getFastRoutePattern(): string {
		return $this->getUriPattern()->getFastRouteFragment();
	}
	
	public function get_dependencies(): \ConstMap<Tx, Dispatcher<Tx, Tv, TState>> {
		return $this->dependencies;
	}
	
	public async function render<T_ super TComparable>(RequestParameters $params, string $path): Awaitable<ViewTree<Tx, T_>> {
		$resolver = $this->resolver;
		$state = $this->state_factory->make($this, $path);
		$view = await $resolver($params, $state);
		return <ViewTree route={$this} view={$view}>
			{$state->get_stashed_viewforest()}
		</ViewTree>;
	}
}