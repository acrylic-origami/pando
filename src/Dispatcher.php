<?hh // strict
namespace Pando;
class Dispatcher<+Tv, +Tk as arraykey, +TRoute as Route<Tv, Tk>> extends RootDispatcher<Tv, Tk, TRoute> {
	public function __construct(Iterable<TRoute> $routes, (function((function(\FastRoute\RouteCollector): void)): \FastRoute\Dispatcher) $fdispatcher = fun('\FastRoute\simpleDispatcher')) {
		parent::__construct($routes, null, $fdispatcher);
	}
}