<?hh // strict
namespace Pando\Tree;
use \Pando\Util\Collection\KeyedContainerWrapper as KC;
// oooooh, just you wait until Tree<+Tv, Tx as arraykey, +TIterable as KeyedIterable<Tx, this>> comes along
// this won't be just any ordinary tree
// oooh no, this'll be the fucking Pando\ of abstract trees

// ...though unfortunately KeyedIterable is still invariant on Tx, and until the <<__Const>> directive is introduced, it'll stay that way.
class Tree<+Tv, +Tx as arraykey> {
	// private KeyedContainerWrapper<Tx, this, KeyedContainer<Tx, this>> $forest;
	private KC<Tx, this> $forest;
	// `this` disallowed as a type constraint forces the third parameter to be `KeyedContainer` rather than a generic `TCollection [as KeyedContainer<Tx, this>]`
	public function __construct(
		?KeyedContainer<Tx, this> $forest,
		private ?Tv $v
		) {
		$this->forest = new KC($forest);
	}
	public final function get_v(): ?Tv {
		return $this->v;
	}
	public final function get_forest(): ?KC<Tx, this> {
		return $this->forest;
	}
	public function reduce_tree<TInitial>((function(?TInitial, ?Tv): ?TInitial) $fn, ?TInitial $initial): ?TInitial {
		// return $this->forest->reduce((?TInitial $prev, this $next) ==> $fn($next->reduce_tree($fn, $prev), $next->get_v()), $initial);
		return $fn($this->forest->reduce((?TInitial $prev, this $next) ==> $next->reduce_tree($fn, $prev), $initial), $this->v);
	}
	/* HH_IGNORE_ERROR[4120] Generator should be covariant on its Tk. */
	public function get_iterator(): \Generator<?Tx, ?Tv, bool> {
		$units = $this->forest->get_units();
		if(!is_null($units)) {
			foreach($units as $k_tree => $tree) {
				foreach($tree->get_iterator() as $k => $v) {
					yield ($k ?? $k_tree) => $v;
				}
			}
			yield null => $this->v;
		}
		else {
			yield null => null;
		}
	}
}