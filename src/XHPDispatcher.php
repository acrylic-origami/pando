<?hh // strict
namespace Shufflr\TreeRouter;

class XHPDispatcher<Tk as arraykey, Tv as \XHPRoot, TRoute as Route<Tv, Tk>> extends Dispatcher<Tk, Tv, TRoute> {
	public function render(string $method, string $uri): Awaitable<Tv> {
		return $this->dispatch($method, $uri)->resolve();
	}
}