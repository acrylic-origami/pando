<?hh // strict
// Obsolete
//
// Not useful enough to warrant separate class.
namespace Pando\TreeRouter;
final class TrivialDispatcher<Tv, Tx as arraykey> extends Dispatcher<Tv, Tx, Route\Default<Tv, Tx>> {
	public function __construct((function(): Awaitable<Tv>) $fn, (function((function(\FastRoute\RouteCollector): void)): \FastRoute\Dispatcher) $fdispatcher = fun('\FastRoute\simpleDispatcher')) {
		parent::__construct(Vector{new Route\Default($fn)}, $fdispatcher);
	}
}