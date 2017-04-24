<?hh // strict
namespace Pando;
use \HHRx\Tree\Tree;
use \HHRx\Tree\MutableTree;
use \HHRx\Util\Collection\KeyedContainerWrapper as KC;
use \HHRx\Util\Collection\IterableIndexAccess as IterableIA;
class Database extends \PandoDB\Database {
	const type IdentifierTree = MutableTree<\HHRx\Stream<\PandoDB\IdentifierCollection>, string>;
	private this::IdentifierTree $identifiers;
	private Vector<this::IdentifierTree> $path;
	private this::IdentifierTree $current_pointer;
	public function __construct(?\HHRx\Stream<\PandoDB\IdentifierCollection> $global_stream) {
		parent::__construct($global_stream);
		$this->identifiers = new MutableTree(new IterableIA(Map{}));
		$this->path = Vector{};
		$this->current_pointer = $this->identifiers;
	}
	public function dig(string $identifier): void {
		/* HH_FIXME[4110] We know that $this->identifiers is exactly MutableTree by the constructor and no other methods modifying it (i.e. it is `const`). The [4110] error reflects the non-const-ness. */
		$this->identifiers->add_subtree($identifier, new MutableTree(new IterableIA(Map{})));
	}
	public function surface(): void {
		try 
			$this->current_pointer = $this->path->pop();
		catch(\InvalidOperationException $e)
			$this->current_pointer = $this->identifiers; // At root; path is empty
	}
	public function get_current(): this::IdentifierTree {
		return $this->current_pointer;
	}
	
	// Deprecated stream filtering methods
	<<__Deprecated('Filter your own streams by iterating through the current identifiers (See `Pando\Database::get_current()`)')>>
	public function collect_substreams(): ?\HHRx\Stream<\PandoDB\IdentifierCollection> {
		return self::_collect_stream_from_tree($this->get_current());
	}
	private static function _collect_stream_from_tree(this::IdentifierTree $tree): ?\HHRx\Stream<\PandoDB\IdentifierCollection> {
		$flat_stream_tree = $tree->reduce(
			(?Vector<\HHRx\Stream<\PandoDB\IdentifierCollection>> $prev, ?\HHRx\Stream<\PandoDB\IdentifierCollection> $next) ==> {
				invariant(!is_null($prev), 'Implementation error: non-null `Vector` passed into `MutableTree::reduce` but null value generated.');
				if(is_null($next))
					return $prev;
				else
					return $prev->add($next);
			},
		Vector{});
		return \HHRx\KeyedStream::merge_all($flat_stream_tree);
	}
}