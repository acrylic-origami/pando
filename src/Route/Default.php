<?hh // strict
namespace Pando\TreeRouter\Route;
use \Pando\TreeRouter\Route;
class Default<Tk, Tv> implements Route {
	public function __construct(
		public (function(): Awaitable<Tv>) $fn, 
		public ?ImmMap<arraykey, \Pando\TreeRouter\Dispatcher<Tk, Tv, this>> $dep) {}
}