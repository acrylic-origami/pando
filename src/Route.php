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

abstract class Route<-TState as State> {
	private Generator<mixed, AsyncGenerator<mixed, ?PartialViewTree, TState>, TState> $fountain_of_views;
	abstract protected function get_method(): HttpMethod;
	public function __construct<T_ as ComparableView<T_>>(
		(function(TState): T_) $resolver,
		private IStateFactory<TState> $state_factory,
		private \ConstMap<string, Dispatcher<TState>> $dependencies,
	) {
		$this->fountain_of_views = $this->view_factory_factory($resolver);
	}
	
	// our little virtual prisons for the T_ type
	private function view_factory_factory<T_ as ComparableView<T_>>(
		(function(TState): T_) $resolver
	): Generator<mixed, AsyncGenerator<mixed, ?PartialViewTree, TState>, TState> {
		$state = yield;
		while(true)
			$state = yield view_factory($resolver, $this, $state);
	}
	final private static async function view_factory<T_ as ComparableView<T_>>(
		(function(TState): T_) $resolver,
		Route<TState> $context,
		TState $state // initial state anyways
	): AsyncGenerator<mixed, ?PartialViewTree, TState> {
		$stashed_view = await $resolver($state);
		$state = yield new PartialViewTree($stashed_view, $state->get_viewforest());
		while(true) {
			$candidate_view = await $resolver($state);
			if($stashed_view->compare($candidate_view)) {
				$stashed_view = $candidate_view;
				$state = yield new PartialViewTree($stashed_view, $state->get_viewforest());
			}
			else
				$state = yield null;
		}
	}
	
	public async function render(RequestParameters $params, Request $request): Awaitable<ViewTree> {
		$this->fountain_of_views->send($this->state_factory->make($this, $request));
		$view_factory = $this->fountain_of_views->current();
		$partial_view_tree = await $view_factory->next();
		invariant(!is_null($partial_view_tree), 'Implementation error: unexpected null on first yield from the view factory');
		return new ViewTree($partial_view_tree, $view_factory);
	}
}