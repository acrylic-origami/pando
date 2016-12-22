<?hh // strict
namespace Pando\TreeRouter\Route\Tracked;
use \Pando\TreeRouter\Route;
use \Pando\TreeRouter\Dispatcher;
class Any<+Tv, +Tx as arraykey> extends Route<Tv, Tx> { // +TComp to generalize comparisons outside of arraykeys (and their precious {>,<}[=])
	// <<__Override>>
	public function __construct(
		public string $path,
		(function(this): Awaitable<(Tv, ?arraykey)>) $fn,
		?ImmMap<arraykey, Dispatcher<Tx, Tv, this>> $dep,
		public (function(?arraykey, ?arraykey): bool) $comparator = (?arraykey $a, ?arraykey $b) ==> $b > $a) {
		parent::__construct($fn, $dep);
	}
}