<?hh // strict
use Facebook\HackRouter\{
	HttpMethod,
	RequestParameters
};
use Psr\Http\Message\RequestInterface;
class State {
	private Map<string, Map<HttpMethod, Map<string, :ViewTree<this>>>> $view_forest = Map{};
	public function __construct(protected Route<this> $context, protected RequestInterface $request) {}
	
	<<__Memoize>>
	public async function at(string $k): Awaitable<\XHPRoot> {
		$dispatcher = $this->context->get_dependencies()->at($k);
		$context = $this->context;
		list($route, $params) = $dispatcher->routeRequest($context::get_method(), (string)$this->request->getUri());
		$view_tree = await $route->render(new RequestParameters($route->getUriPattern()->getParameters(), ImmVector{}, $params), $this->request);
		$this->view_forest->set($k, Map{ $route::get_method() => Map{ $route->getFastRoutePattern() => $view_tree }});
		return $view_tree;
	}
	// public function get_immutable_view_forest(): \ConstMap<string, :ViewTree<this>> {
	// 	return $this->get_view_forest();
	// }
	public function get_view_forest(): Map<string, Map<HttpMethod, Map<string, :ViewTree<this>>>> {
		return $this->view_forest;
	}
}