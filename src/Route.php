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
	private ?(RequestParameters, Request) $request = null;
	private AsyncGenerator<mixed, ?\XHPRoot, ComparerAction> $spawner;
	abstract protected function get_method(): HttpMethod;
	public function __construct<+T_ as Comparable<T_>>(
		(function(RequestParameters, TState): T_) $resolver,
		private IStateFactory<TState> $state_factory,
		private \ConstMap<string, Dispatcher<TState>> $dependencies,
	) {
		$this->spawner = spawner($resolver, $this);
	}
	public function get_request(): (RequestParameters, Request) {
		invariant(!is_null($this->request), 'Attempted to get request before request was sent (before `render` was called).')
	}
	public function render(RequestParameters $params, Request $request): AsyncIterator<?\XHPRoot> {
		$this->request = tuple($params, $request);
		return clone $this->spawner;
	}
}