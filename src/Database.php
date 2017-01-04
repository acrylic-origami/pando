<?hh // strict
namespace Pando;
use \Pando\Tree\Tree;
use \Pando\Tree\MutableTree;
use \Pando\Util\Collection\KeyedContainerWrapper as KC;
use \Pando\Util\Collection\IterableIndexAccess as IterableIA;
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
	// unsure why this type error exists: $this->identifiers is precisely MutableTree _and_ private!
	public function dig(string $identifier): void {
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
	public function collect_substreams(): \HHRx\Stream<\PandoDB\IdentifierCollection> {
		return self::_collect_stream_from_tree($this->get_current());
	}
	<<__Deprecated('Filter your own streams by iterating through the current identifiers (See `Pando\Database::get_current_identifiers()`)')>>
	private static function _collect_stream_from_tree(this::IdentifierTree $tree): \HHRx\Stream<\PandoDB\IdentifierCollection> {
		$subtrees = $tree->get_forest();
		$substreams = $subtrees->keyed_reduce(
			(Map<mixed, \HHRx\Stream<\PandoDB\IdentifierCollection>> $prev, Pair<string, this::IdentifierTree> $subtree) ==> {
				$substream = $subtree[1]->get_v();
				if(!is_null($substream))
					$prev->add(Pair{ $subtree[0], $substream });
				return $prev->addAll(self::_collect_stream_from_tree($subtree[1]));
			},
		Map{});
		return \HHRx\KeyedStream::merge_all($substreams);
	}
}