<?hh // strict
namespace Pando\Tree;
// use \Pando\KeyedContainerWrapper as KC;
abstract class FutureKeyedTree<+Tv, +Tx as arraykey> extends Tree<Tv, Tx> {
	private ?Tv $v; // shadow private variable from Tree... waiting for that object-protected
	public function __construct(
		?KeyedContainer<Tx, this> $forest,
		?Tv $v = null) {
		parent::__construct($forest, $v);
		$this->v = $v;
	}
	
	<<__Override>>
	public function get_v(): ?Tv { // thanks to object slicing and there not being an instance version of late static binding, the base class has no way of calling this method :/
		// but hey! at least subclasses will hit this v instead of the Tree v.
		return $this->v;
	}
	// [OBSOLETE?] Future me isn't sure why I wanted FKT to have its own $v anyways; when would I ever modify it? Just `get_v` from parent Tree.
	
	// not going to fly with full covariance
	// public function put(?KC<Tk, Awaitable<this>> $subtree): this {
	// 	$this->subtree = $subtree;
	// 	return $this; // for chaining
	// }
	// public async function get(Tk $k): Awaitable<this> {
	// 	invariant(!is_null($this->subtree), 'Subtree is null, cannot FKT::get.');
	// 	$subtree = $this->subtree; // eff the typechecker sometimes.
	// 	if($subtree->containsKey($k))
	// 		return await $subtree[$k];
	// 	else
	// 		throw new \OutOfBoundsException('Key `'.$k.'` not found in FKT::get.');
	// }
	public async function resolve(): Awaitable<Tv> {
		return $this->v ?? ($this->v = await $this->_resolve()); // the memoizing function
	}
	abstract protected async function _resolve(): Awaitable<Tv>; // `get()` can make use of $this->subtree as the argument to develop its values
		// this is the method defined by anonymous classes that represent routes.
}