<?hh // strict
namespace Pando\TreeRouter;
final class TrivialDispatcher<Tk, Tv> extends Dispatcher<Tk, Tv> {
	public function __construct((function(): Awaitable<Tv>) $fn) {
		parent::__construct(new Route\Default($fn));
	}
}