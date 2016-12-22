<?hh // strict
namespace Pando\Route;
class Default<Tv, Tk as arraykey> implements Route<Tv, Tk> {
	public function __construct(
		public (function(): Awaitable<Tv>) $fn, 
		public ?ImmMap<arraykey, \Pando\Dispatcher<Tv, Tk, this>> $dep) {}
}