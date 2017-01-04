<?hh // strict
namespace Pando;

class XHPDispatcher<Tv as \XHPRoot, Tk as arraykey, TRoute as Route<Tv, Tk>> extends Dispatcher<Tv, Tk, TRoute> {
	public function render(string $method, string $uri): Awaitable<Tv> {
		return $this->dispatch($method, $uri)->resolve();
	}
}