<?hh // strict
namespace Shufflr;
class JSONParser implements Parser {
	public function __construct() {}
	public function parse(string $str): ?\KeyedContainer<arraykey, mixed> {
		$A = json_decode($str);
		$M = $this->_recurse($A);
		invariant(is_null($A) || $A instanceof Traversable, 'Recursion does not return a ?Traversable');
		return is_array($A) ? new ImmVector($M) : $M;
	}
	public function serialize_identifiable(Identifiable<arraykey, DependencyPackage> $identifiable): ?string {
		$subidentifiables = $identifiable->get_subidentifiables();
		if(!is_null($subidentifiables)) {
			foreach($subidentifiables as $subidentifiable) {
				
			}
		}
	}
	public function serialize_tree<Tv, Tx as arraykey>(?Tree<Tv, Tx> $tree): ?string {
		if(!is_null($tree)) {
			$forest = $tree->get_forest();
			if(!is_null($forest))
				return json_encode(Pair{
					$tree->get_v(), 
					$forest->keyed_reduce(
						(?Map<Tx, string> $prev, Pair<Tx, Tree<Tv, Tx>> $next) ==> {
							if(!is_null($prev))
								return $prev->add(Pair{$next[0], $this->serialize_tree($next[1]) ?? ""});
						},
						Map{})
					}
				);
			else
				return json_encode(Pair{ $tree->get_v(), null });
		}
		else
			return null;
	}
	public function serialize(mixed $obj): ?string {
		return json_encode($obj);
	}
	protected function _recurse(mixed $A): ?\KeyedContainer<arraykey, mixed> {
		$M = Map{};
		if(is_object($A) && is_null($A = get_object_vars($A)))
			return null;
		
		invariant(is_array($A), 'A is not an array.');
		foreach($A as $k=>$v) {
			if($v instanceof \stdClass) {
				$M[$k] = $this->_recurse($v);
			}
			elseif(is_array($v)) {
				$next = $this->_recurse($v);
				invariant(is_null($next) || $next instanceof Traversable, 'Recursion does not return a ?Traversable');
				$M[$k] = new ImmVector($next);
			}
			else {
				$M[$k] = $v;
			}
		}
		return new ImmMap($M);
	}
}