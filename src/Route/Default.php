<?hh // strict
namespace Shufflr\TreeRouter\Route;
use \Shufflr\TreeRouter\Route;
class Default<Tk, Tv> implements Route {
	public function __construct(
		public (function(): Awaitable<Tv>) $fn, 
		public ?ImmMap<arraykey, \Shufflr\TreeRouter\Dispatcher<Tk, Tv, this>> $dep) {}
}