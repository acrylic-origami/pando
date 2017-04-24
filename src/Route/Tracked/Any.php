<?hh // strict
namespace Pando\Route\Tracked;
use \Pando\Route;
use \Pando\Dispatcher;
use \HHRx\Util\Collection\IterableConstIndexAccess as IterableCIA;
use \HHRx\Util\Collection\ConstMapCIA;
class Any<+Tv, Tx as arraykey> extends Route<Tv, Tx> { // +TComp to generalize comparisons outside of arraykeys (and their precious {>,<}[=])
	// <<__Override>>
	public function __construct(
		public string $path,
		(function(IterableCIA<Tx, (Tv, ?arraykey), \ConstMap<Tx, (Tv, ?arraykey)>>): (Tv, ?arraykey)) $resolver,
		?ConstMapCIA<Tx, Dispatcher<Tv, Tx, this>> $dependencies = null,
		public (function(?arraykey, ?arraykey): bool) $comparator = (?arraykey $a, ?arraykey $b) ==> $b > $a) {
		parent::__construct($path, $resolver, $dependencies);
	}
}