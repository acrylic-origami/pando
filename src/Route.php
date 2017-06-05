<?hh // strict
use Pando\{
	Identifiable,
	State\IStateFactory,
	State\State,
	ViewFactory,
	ComparableView,
	Dispatcher
};

use Facebook\HackRouter\{
	UriPattern,
	HttpMethod,
	RequestParameters
};

use Psr\Http\Message\RequestInterface;

abstract class Route<-TState as \State> {
	private \Generator<int, ?ViewFactory<TState>, TState> $fountain_of_views;
	public function __construct<T_ as ComparableView<T_>>(
		private UriPattern $uri_pattern,
		(function(TState): Awaitable<T_>) $resolver,
		private IStateFactory<TState> $state_factory,
		private \ConstMap<string, Dispatcher<TState>> $dependencies,
	) {
		$this->fountain_of_views = $this->view_factory_factory($resolver);
	}
	
	abstract public static function get_method(): HttpMethod;
	
	public function get_dependencies(): \ConstMap<string, Dispatcher<TState>> {
		return $this->dependencies;
	}
	public function getUriPattern(): UriPattern {
		return $this->uri_pattern;
	}
	public function getFastRoutePattern(): string {
		return $this->getUriPattern()->getFastRouteFragment();
	}
	
	// our little virtual prisons for the T_ type
	private function view_factory_factory<T_ as ComparableView<T_>>(
		(function(TState): Awaitable<T_>) $resolver
	): \Generator<int, ?ViewFactory<TState>, TState> {
		$state = yield null;
		while(true) {
			invariant(!is_null($state), 'Unexpected `null` state given to %s. Hint: do not call `next` on this generator', __METHOD__);
			$state = yield self::view_factory($resolver, $this, $state);
		}
	}
	private static async function view_factory<T_ as ComparableView<T_>, TRetState as TState>(
		(function(TState): Awaitable<T_>) $resolver,
		Route<TState> $context,
		TState $state // _initial_ state anyways
	): ViewFactory<TRetState> {
		$stashed_view = await $resolver($state);
		$state = yield new \PartialViewTree($stashed_view->get_view(), $state->get_view_forest());
		while(true) {
			invariant(!is_null($state), 'Unexpected `null` state given to %s. Hint: do not call `next` on this generator', __METHOD__);
			$candidate_view = await $resolver($state);
			if($stashed_view->compare($candidate_view)) {
				$stashed_view = $candidate_view;
				$state = yield new \PartialViewTree($stashed_view->get_view(), $state->get_view_forest());
			}
			else
				$state = yield null;
		}
	}
	
	private static function make_identifiable<T_ as ComparableView<T_>>(T_ $v, ?string $prev_identity = null): Identifiable {
		return <pando:div-identifiable id={$prev_identity}>
			{$v}
		</pando:div-identifiable>;
	}
	
	public async function render(RequestParameters $params, RequestInterface $request): Awaitable<:ViewTree<TState>> {
		$this->fountain_of_views->send($this->state_factory->make($this, $request));
		$view_factory = $this->fountain_of_views->current();
		invariant(!is_null($view_factory), 'Implementation error: unexpected `null` from view factory after sending state (shouldn\'t send null unless `current` is called before `send`)');
		$pair = await $view_factory->next();
		invariant(!is_null($pair), 'View factory terminated unexpectedly');
		$partial_view_tree = $pair[1];
		invariant(!is_null($partial_view_tree), 'Implementation error: unexpected null on first yield from the view factory');
		return <ViewTree partial={$partial_view_tree} view_factory={$view_factory} />;
	}
}